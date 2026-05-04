<?php

namespace App\Http\Controllers;

use App\Mail\CourseApplicationAdminNotificationMail;
use App\Mail\CourseApplicationConfirmationMail;
use App\Models\ActualCourse;
use App\Models\CourseApplication;
use App\Models\UserSetting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class CourseApplicationController extends Controller
{
    public function create()
    {
        $actualCourses = ActualCourse::query()
            ->whereHas('course', fn ($q) => $q->where('is_active', true))
            ->orderBy('start_date')
            ->with(['course.courseCategory', 'days'])
            ->get();

        $categories = \App\Models\CourseCategory::query()
            ->with(['courses' => fn ($q) => $q->where('is_active', true)->orderBy('name')])
            ->orderBy('name')
            ->get();

        $tiles = \App\Models\Tile::query()
            ->get();

        return view('courses.application', compact('actualCourses', 'categories', 'tiles'));
    }

    public function index()
    {
        $actualCourses = ActualCourse::query()
            ->whereHas('course', fn ($q) => $q->where('is_active', true)->where('listed', true))
            ->orderBy('start_date')
            ->with(['course.courseCategory', 'days'])
            ->get();

        $categories = \App\Models\CourseCategory::query()
            ->with(['courses' => fn ($q) => $q->where('is_active', true)->orderBy('name')])
            ->orderBy('sort_order')
            ->get();

        return view('courses.index', compact('actualCourses', 'categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'actual_course_id' => ['required', 'exists:actual_courses,id'],
            'certificate_language' => ['nullable', 'in:Angol,Német'],

            'payer_name' => ['required', 'string', 'max:255'],
            'payer_address' => ['required', 'string', 'max:255'],
            'payer_mailing_address' => ['required', 'string', 'max:255'],
            'payer_email' => ['required', 'email', 'max:255'],
            'payer_signatory' => ['required', 'string', 'max:255'],
            'payer_tax_number' => ['required', 'string', 'max:255'],

            'participant_last_name' => ['required', 'string', 'max:255'],
            'participant_first_name' => ['required', 'string', 'max:255'],
            'participant_birth_name' => ['required', 'string', 'max:255'],
            'participant_birth_place' => ['required', 'string', 'max:255'],
            'participant_birth_country' => ['required', 'string', 'max:255'],
            'participant_birth_date' => ['required', 'string', 'max:255'],
            'participant_address' => ['required', 'string', 'max:255'],
            'participant_notification_address' => ['required', 'string', 'max:255'],
            'participant_phone' => ['required', 'string', 'max:255'],
            'participant_email' => ['required', 'email', 'max:255'],
            'participant_mother_name' => ['required', 'string', 'max:255'],
            'participant_education' => ['nullable', 'string', 'max:255'],
            'participant_education_id' => ['nullable', 'string', 'max:255'],
            'participant_supported' => ['nullable', 'in:igen,nem'],
            'participant_grant_id' => ['nullable', 'string', 'max:255'],

            'newsletter_opt_in' => ['nullable', 'boolean'],
            'privacy_accepted' => ['required', 'accepted'],

            'robot_field' => ['nullable', 'string', 'max:255'],
        ]);

        if (!empty($data['robot_field'])) {
            abort(422);
        }

        $actualCourse = ActualCourse::query()->findOrFail($data['actual_course_id']);

        if (
            $actualCourse->application_deadline &&
            Carbon::parse($actualCourse->application_deadline)->endOfDay()->isPast()
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'actual_course_id' => 'A jelentkezési határidő lejárt erre a tanfolyamra.',
                ]);
        }

        $data['newsletter_opt_in'] = (bool) $request->boolean('newsletter_opt_in');
        $data['privacy_accepted'] = true;

        $application = CourseApplication::query()->create($data);
        $application->load('actualCourse.course.courseCategory');

        Mail::to($application->participant_email)
            ->send(new CourseApplicationConfirmationMail($application));

        $adminEmail = UserSetting::getValueByName('admin-email-address');

        if (! empty($adminEmail)) {
            Mail::to($adminEmail)
                ->send(new CourseApplicationAdminNotificationMail($application));
        }

        return back()->with('success', 'Sikeres jelentkezés! Hamarosan e-mailben jelentkezünk.');
    }
}
