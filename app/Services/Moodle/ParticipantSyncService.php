<?php

namespace App\Services\Moodle;

use App\Models\CourseApplication;
use Illuminate\Support\Str;
use Throwable;

class ParticipantSyncService
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SYNCED = 'synced';

    public const STATUS_FAILED = 'failed';

    public function __construct(
        private readonly MoodleClient $client,
        private readonly MoodleParticipantPayloadMapper $payloadMapper,
        private readonly MoodleUsernameGenerator $usernameGenerator,
    ) {}

    public function sync(CourseApplication $application): CourseApplication
    {
        $this->markPending($application);

        try {
            match ($application->status) {
                'PROCESSED' => $this->syncProcessed($application),
                'CANCELLED' => $this->syncCancelled($application),
                default => throw new MoodleException('Only processed or cancelled applications can be synchronized to Moodle.'),
            };

            $application->forceFill([
                'moodle_sync_status' => self::STATUS_SYNCED,
                'moodle_last_synced_at' => now(),
                'moodle_sync_error' => null,
            ])->saveQuietly();

            return $application->refresh();
        } catch (Throwable $exception) {
            $application->forceFill([
                'moodle_sync_status' => self::STATUS_FAILED,
                'moodle_sync_error' => $this->safeDiagnosticMessage($exception),
            ])->saveQuietly();

            throw $exception;
        }
    }

    private function syncProcessed(CourseApplication $application): void
    {
        $courseId = $this->moodleCourseId($application);
        $participant = $this->participantPayload($application);
        $moodleUserId = $application->moodle_user_id;

        if ($moodleUserId !== null) {
            $this->updateUser($moodleUserId, $participant);
        } else {
            [$moodleUserId, $created] = $this->resolveUser($participant);
            $application->forceFill(['moodle_user_id' => $moodleUserId])->saveQuietly();

            if (! $created) {
                $this->updateUser($moodleUserId, $participant);
            }
        }

        $this->setEnrolmentSuspended($courseId, $moodleUserId, false);
    }

    private function syncCancelled(CourseApplication $application): void
    {
        $courseId = $application->actualCourse?->moodle_course_id;
        $userId = $application->moodle_user_id;

        if ($courseId === null || $userId === null) {
            return;
        }

        $this->setEnrolmentSuspended($courseId, $userId, true);
        $this->updateUser($userId, $this->participantPayload($application));
    }

    private function moodleCourseId(CourseApplication $application): int
    {
        $courseId = $application->actualCourse?->moodle_course_id;

        if (! is_int($courseId) || $courseId < 1) {
            throw new MoodleException('The application course has not been synchronized to Moodle yet.');
        }

        return $courseId;
    }

    private function participantPayload(CourseApplication $application): array
    {
        $payload = $this->payloadMapper->map($application);
        $firstName = trim((string) ($payload['participant_first_name'] ?? ''));
        $lastName = trim((string) ($payload['participant_last_name'] ?? ''));
        $email = Str::lower(trim((string) ($payload['participant_email'] ?? '')));

        if ($firstName === '' || $lastName === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new MoodleException('Participant first name, last name and a valid email address are required.');
        }

        return [
            'firstname' => $firstName,
            'lastname' => $lastName,
            'email' => $email,
        ];
    }

    /** @return array{int, bool} */
    private function resolveUser(array $participant): array
    {
        $result = $this->client->call('core_user_get_users_by_field', [
            'field' => 'email',
            'values' => [$participant['email']],
        ]);

        if (! is_array($result) || ! array_is_list($result)) {
            throw new MoodleException(
                'Moodle user lookup returned an invalid response.',
                moodleFunction: 'core_user_get_users_by_field',
            );
        }

        if (count($result) > 1) {
            throw new MoodleException(
                'Moodle user lookup returned more than one exact email match.',
                moodleFunction: 'core_user_get_users_by_field',
            );
        }

        if ($result === []) {
            return [$this->createUser($participant), true];
        }

        $userId = $result[0]['id'] ?? null;
        $returnedEmail = Str::lower(trim((string) ($result[0]['email'] ?? '')));

        if (! is_numeric($userId) || (int) $userId < 1 || $returnedEmail !== $participant['email']) {
            throw new MoodleException(
                'Moodle user lookup returned an invalid exact email match.',
                moodleFunction: 'core_user_get_users_by_field',
            );
        }

        return [(int) $userId, false];
    }

    private function createUser(array $participant): int
    {
        $auth = trim((string) config('moodle.user_auth'));

        if ($auth === '') {
            throw new MoodleException('Missing Moodle user authentication configuration.');
        }

        $result = $this->client->call('core_user_create_users', [
            'users' => [[
                'username' => $this->usernameGenerator->generate($participant['email']),
                'firstname' => $participant['firstname'],
                'lastname' => $participant['lastname'],
                'email' => $participant['email'],
                'auth' => $auth,
                'createpassword' => (bool) config('moodle.user_create_password'),
            ]],
        ]);

        if (! is_array($result) || ! array_is_list($result) || count($result) !== 1) {
            throw new MoodleException(
                'Moodle user creation returned an invalid response.',
                moodleFunction: 'core_user_create_users',
            );
        }

        $userId = $result[0]['id'] ?? null;

        if (! is_numeric($userId) || (int) $userId < 1) {
            throw new MoodleException(
                'Moodle user creation returned an invalid user ID.',
                moodleFunction: 'core_user_create_users',
            );
        }

        return (int) $userId;
    }

    private function updateUser(int $moodleUserId, array $participant): void
    {
        $this->client->call('core_user_update_users', [
            'users' => [[
                'id' => $moodleUserId,
                'firstname' => $participant['firstname'],
                'lastname' => $participant['lastname'],
                'email' => $participant['email'],
            ]],
        ]);
    }

    private function setEnrolmentSuspended(int $courseId, int $userId, bool $suspended): void
    {
        $roleId = config('moodle.student_role_id');

        if (! is_numeric($roleId) || (int) $roleId < 1) {
            throw new MoodleException('Missing or invalid Moodle student role ID.');
        }

        $this->client->call('enrol_manual_enrol_users', [
            'enrolments' => [[
                'roleid' => (int) $roleId,
                'userid' => $userId,
                'courseid' => $courseId,
                'suspend' => $suspended ? 1 : 0,
            ]],
        ]);
    }

    private function markPending(CourseApplication $application): void
    {
        $application->forceFill(['moodle_sync_status' => self::STATUS_PENDING])->saveQuietly();
    }

    private function safeDiagnosticMessage(Throwable $exception): string
    {
        $message = $exception->getMessage();
        $token = (string) config('moodle.web_service_token', '');

        if ($token !== '') {
            $message = str_replace([$token, urlencode($token)], '[redacted]', $message);
        }

        return Str::limit('Moodle participant synchronization failed: '.$message, 2000, '');
    }
}
