<?php

namespace App\Services\Moodle;

use App\Models\ActualCourse;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Throwable;

class CourseSyncService
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SYNCED = 'synced';

    public const STATUS_FAILED = 'failed';

    public function __construct(
        private readonly MoodleClient $client,
        private readonly MoodleCoursePayloadMapper $payloadMapper,
    ) {}

    public function sync(
        ActualCourse $actualCourse,
        bool $visible,
        ?int $categoryId = null,
    ): ActualCourse {
        $this->markPending($actualCourse);

        try {
            $payload = $this->coursePayload(
                $actualCourse,
                $visible,
                $this->categoryId($categoryId),
            );

            if ($actualCourse->moodle_course_id !== null) {
                $this->updateCourse($actualCourse->moodle_course_id, $payload);
            } else {
                $moodleCourseId = $this->findCourseIdByIdnumber($payload['idnumber']);

                if ($moodleCourseId !== null) {
                    $actualCourse->forceFill(['moodle_course_id' => $moodleCourseId])->save();
                    $this->updateCourse($moodleCourseId, $payload);
                } else {
                    $actualCourse->forceFill([
                        'moodle_course_id' => $this->createCourse($payload),
                    ])->save();
                }
            }

            $actualCourse->forceFill([
                'moodle_sync_status' => self::STATUS_SYNCED,
                'moodle_last_synced_at' => now(),
                'moodle_sync_error' => null,
            ])->save();

            return $actualCourse->refresh();
        } catch (Throwable $exception) {
            $actualCourse->forceFill([
                'moodle_sync_status' => self::STATUS_FAILED,
                'moodle_sync_error' => $this->safeDiagnosticMessage($exception),
            ])->save();

            throw $exception;
        }
    }

    private function markPending(ActualCourse $actualCourse): void
    {
        $actualCourse->forceFill([
            'moodle_sync_status' => self::STATUS_PENDING,
        ])->save();
    }

    private function categoryId(?int $categoryId): int
    {
        $resolved = $categoryId ?? config('moodle.course_category_id');

        if (! is_numeric($resolved) || (int) $resolved < 1) {
            throw new MoodleException(
                'Missing or invalid Moodle course category ID.',
                moodleFunction: 'core_course_create_courses',
            );
        }

        return (int) $resolved;
    }

    private function coursePayload(ActualCourse $actualCourse, bool $visible, int $categoryId): array
    {
        $internal = $this->payloadMapper->map($actualCourse);
        $courseName = trim((string) ($internal['course_name'] ?? ''));
        $startDate = $internal['start_date'] ?? null;
        $namespace = $this->courseNamespace();

        if ($courseName === '' || blank($startDate)) {
            throw new MoodleException('Moodle course name and start date are required.');
        }

        $payload = [
            'fullname' => $courseName.' – '.$startDate,
            'shortname' => 'IMA-'.strtoupper($namespace).'-'.$actualCourse->getKey(),
            'idnumber' => 'imakademia-'.$namespace.'-actual-course-'.$actualCourse->getKey(),
            'categoryid' => $categoryId,
            'startdate' => $this->dateTimestamp($startDate),
            'visible' => $visible ? 1 : 0,
        ];

        if (filled($internal['end_date'] ?? null)) {
            $payload['enddate'] = $this->dateTimestamp($internal['end_date']);
        }

        return $payload;
    }

    private function courseNamespace(): string
    {
        $namespace = Str::slug(trim((string) config('moodle.course_namespace')), '-');

        if ($namespace === '') {
            throw new MoodleException('Missing or invalid Moodle course namespace.');
        }

        return $namespace;
    }

    private function dateTimestamp(string $date): int
    {
        return Carbon::parse($date, config('app.timezone'))->startOfDay()->timestamp;
    }

    private function findCourseIdByIdnumber(string $idnumber): ?int
    {
        $result = $this->client->call('core_course_get_courses_by_field', [
            'field' => 'idnumber',
            'value' => $idnumber,
        ]);

        if (! is_array($result) || ! is_array($result['courses'] ?? null)) {
            throw new MoodleException(
                'Moodle course lookup returned an invalid response.',
                moodleFunction: 'core_course_get_courses_by_field',
            );
        }

        $courses = array_values($result['courses']);

        if (count($courses) > 1) {
            throw new MoodleException(
                "Ambiguous Moodle course lookup for idnumber {$idnumber}.",
                moodleFunction: 'core_course_get_courses_by_field',
            );
        }

        if ($courses === []) {
            return null;
        }

        $courseId = $courses[0]['id'] ?? null;
        $returnedIdnumber = $courses[0]['idnumber'] ?? $idnumber;

        if ($returnedIdnumber !== $idnumber || ! is_numeric($courseId) || (int) $courseId < 1) {
            throw new MoodleException(
                "Moodle course lookup returned an invalid match for idnumber {$idnumber}.",
                moodleFunction: 'core_course_get_courses_by_field',
            );
        }

        return (int) $courseId;
    }

    private function createCourse(array $payload): int
    {
        $result = $this->client->call('core_course_create_courses', [
            'courses' => [$payload],
        ]);

        if (! is_array($result) || count($result) !== 1) {
            throw new MoodleException(
                'Moodle course creation returned an invalid response.',
                moodleFunction: 'core_course_create_courses',
            );
        }

        $courseId = $result[0]['id'] ?? null;

        if (! is_numeric($courseId) || (int) $courseId < 1) {
            throw new MoodleException(
                'Moodle course creation returned an invalid course ID.',
                moodleFunction: 'core_course_create_courses',
            );
        }

        return (int) $courseId;
    }

    private function updateCourse(int $moodleCourseId, array $payload): void
    {
        $this->client->call('core_course_update_courses', [
            'courses' => [array_merge(['id' => $moodleCourseId], $payload)],
        ]);
    }

    private function safeDiagnosticMessage(Throwable $exception): string
    {
        $message = $exception->getMessage();
        $token = (string) config('moodle.web_service_token', '');

        if ($token !== '') {
            $message = str_replace([$token, urlencode($token)], '[redacted]', $message);
        }

        return Str::limit('Moodle course synchronization failed: '.$message, 2000, '');
    }
}
