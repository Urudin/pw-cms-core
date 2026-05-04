<?php

namespace Tests\Feature;

use App\Mail\CourseApplicationAdminNotificationMail;
use App\Mail\CourseApplicationConfirmationMail;
use App\Models\ActualCourse;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\UserSetting;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CourseApplicationNotificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->createTestSchema();
    }

    public function test_successful_course_application_sends_applicant_and_admin_emails(): void
    {
        Mail::fake();

        UserSetting::query()->create([
            'name' => 'admin-email-address',
            'value' => 'admin@example.com',
        ]);

        $category = CourseCategory::query()->create([
            'name' => 'Minősített oktatás',
        ]);

        $course = Course::query()->create([
            'course_category_id' => $category->id,
            'name' => 'Innovációmenedzsment alapképzés',
            'price' => 120000,
            'is_active' => true,
            'listed' => true,
        ]);

        $actualCourse = ActualCourse::query()->create([
            'course_id' => $course->id,
            'classification' => 'Minősített oktatás',
            'user_given_id' => 123,
            'place_of_event' => '1051 Budapest, Arany János u. 15.',
            'start_date' => '2026-06-15',
            'end_date' => '2026-06-16',
            'application_deadline' => '2026-06-10',
            'way_of_participation' => 'group',
            'price' => 150000,
            'min_participants' => 1,
            'max_participants' => 15,
        ]);

        $response = $this
            ->from(route('course-applications.create'))
            ->post(route('course-applications.store'), $this->validPayload($actualCourse->id));

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('course-applications.create'));

        $this->assertDatabaseHas('course_applications', [
            'participant_email' => 'applicant@example.com',
            'payer_name' => 'Példa Kft.',
        ]);

        Mail::assertSent(CourseApplicationConfirmationMail::class, function (CourseApplicationConfirmationMail $mail) {
            $body = $mail->render();

            return $mail->hasTo('applicant@example.com')
                && str_contains($body, 'Teszt Elek')
                && str_contains($body, 'Innovációmenedzsment alapképzés')
                && str_contains($body, '2026.06.15.')
                && str_contains($body, '150 000 Ft')
                && str_contains($body, 'nem jelent automatikusan felvételt');
        });

        Mail::assertSent(CourseApplicationAdminNotificationMail::class, function (CourseApplicationAdminNotificationMail $mail) {
            $body = $mail->render();

            return $mail->hasTo('admin@example.com')
                && str_contains($body, 'Példa Kft.')
                && str_contains($body, 'Teszt Elek')
                && str_contains($body, 'applicant@example.com')
                && str_contains($body, 'AB-123456');
        });
    }

    public function test_course_application_form_uses_relative_post_action_and_includes_csrf_token(): void
    {
        $actualCourse = $this->createActualCourse();

        $response = $this->get(route('course-applications.create'));

        $response
            ->assertOk()
            ->assertSee('action="/jelentkezes"', false)
            ->assertSee('name="_token"', false)
            ->assertDontSee('action="https://', false);
    }

    public function test_invalid_course_application_does_not_send_emails(): void
    {
        Mail::fake();

        $response = $this->post(route('course-applications.store'), []);

        $response->assertSessionHasErrors([
            'actual_course_id',
            'payer_name',
            'participant_email',
            'privacy_accepted',
        ]);

        $this->assertDatabaseCount('course_applications', 0);
        Mail::assertNothingSent();
    }

    private function validPayload(int $actualCourseId): array
    {
        return [
            'actual_course_id' => $actualCourseId,
            'certificate_language' => 'Angol',
            'payer_name' => 'Példa Kft.',
            'payer_address' => '1051 Budapest, Példa utca 1.',
            'payer_mailing_address' => '1051 Budapest, Postafiók 1.',
            'payer_email' => 'payer@example.com',
            'payer_signatory' => 'Képviselő Károly',
            'payer_tax_number' => '12345678-1-42',
            'participant_last_name' => 'Teszt',
            'participant_first_name' => 'Elek',
            'participant_birth_name' => 'Teszt Elek',
            'participant_birth_place' => 'Budapest',
            'participant_birth_country' => 'Magyarország',
            'participant_birth_date' => '1990-01-01',
            'participant_address' => '1051 Budapest, Résztvevő utca 2.',
            'participant_notification_address' => '1051 Budapest, Értesítés utca 3.',
            'participant_phone' => '+36 30 123 4567',
            'participant_email' => 'applicant@example.com',
            'participant_mother_name' => 'Minta Mária',
            'participant_education' => 'Felsőfokú végzettség',
            'participant_education_id' => 'AB-123456',
            'participant_supported' => 'igen',
            'participant_grant_id' => 'P-2026-001',
            'newsletter_opt_in' => '1',
            'privacy_accepted' => '1',
            'robot_field' => '',
        ];
    }

    private function createActualCourse(): ActualCourse
    {
        $category = CourseCategory::query()->create([
            'name' => 'Minősített oktatás',
        ]);

        $course = Course::query()->create([
            'course_category_id' => $category->id,
            'name' => 'Innovációmenedzsment alapképzés',
            'price' => 120000,
            'is_active' => true,
            'listed' => true,
        ]);

        return ActualCourse::query()->create([
            'course_id' => $course->id,
            'classification' => 'Minősített oktatás',
            'user_given_id' => 123,
            'place_of_event' => '1051 Budapest, Arany János u. 15.',
            'start_date' => '2026-06-15',
            'end_date' => '2026-06-16',
            'application_deadline' => '2026-06-10',
            'way_of_participation' => 'group',
            'price' => 150000,
            'min_participants' => 1,
            'max_participants' => 15,
        ]);
    }

    private function createTestSchema(): void
    {
        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('value')->nullable();
            $table->timestamps();
        });

        DB::table('user_settings')->insert([
            ['name' => 'header-background', 'value' => '#', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'header-sub-title-row-1', 'value' => 'INNOVÁCIÓMENEDZSMENT', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'header-sub-title-row-2', 'value' => 'SZOLGÁLTATÁSOK', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_url');
            $table->string('to_url');
            $table->timestamps();
        });

        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('model_type')->nullable();
            $table->string('collection_name');
            $table->string('file_name');
            $table->timestamps();
        });

        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->timestamps();
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('menu_id');
            $table->nullableMorphs('menuable');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('_lft')->default(0);
            $table->integer('_rgt')->default(0);
            $table->string('name');
            $table->string('type')->default('url');
            $table->string('url')->nullable();
            $table->string('target')->default('_self');
            $table->string('link_title')->nullable();
            $table->timestamps();
        });

        DB::table('menus')->insert([
            'id' => 1,
            'name' => 'Main menu',
            'slug' => 'main-menu',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('menu_items')->insert([
            'menu_id' => 1,
            'name' => 'Főoldal',
            'type' => 'url',
            'url' => '/',
            'target' => '_self',
            '_lft' => 1,
            '_rgt' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::create('course_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_category_id');
            $table->string('name');
            $table->unsignedBigInteger('price');
            $table->boolean('is_active')->default(true);
            $table->boolean('listed')->default(true);
            $table->longText('description')->nullable();
            $table->timestamps();
        });

        Schema::create('actual_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id');
            $table->string('classification')->nullable();
            $table->unsignedBigInteger('user_given_id')->nullable();
            $table->string('place_of_event')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->date('application_deadline')->nullable();
            $table->string('way_of_participation');
            $table->integer('price')->nullable();
            $table->smallInteger('min_participants')->default(0);
            $table->smallInteger('max_participants')->default(15);
            $table->timestamps();
        });

        Schema::create('actual_course_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actual_course_id');
            $table->date('day');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->timestamps();
        });

        Schema::create('tiles', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->longText('content')->nullable();
            $table->timestamps();
        });

        Schema::create('blocks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->longText('content')->nullable();
            $table->timestamps();
        });

        DB::table('blocks')->insert([
            ['name' => 'PreFooter', 'content' => '', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Footer', 'content' => '', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'PostFooter', 'content' => '', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::create('course_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actual_course_id');
            $table->string('certificate_language')->nullable();
            $table->string('payer_name');
            $table->string('payer_address');
            $table->string('payer_mailing_address');
            $table->string('payer_email')->default('');
            $table->string('payer_signatory');
            $table->string('payer_tax_number');
            $table->string('participant_last_name');
            $table->string('participant_first_name');
            $table->string('participant_birth_name');
            $table->string('participant_birth_place');
            $table->string('participant_birth_country');
            $table->string('participant_birth_date');
            $table->string('participant_address');
            $table->string('participant_notification_address');
            $table->string('participant_phone');
            $table->string('participant_email');
            $table->string('participant_mother_name');
            $table->string('participant_education')->nullable();
            $table->string('participant_education_id')->nullable();
            $table->enum('participant_supported', ['igen', 'nem'])->nullable();
            $table->string('participant_grant_id')->nullable();
            $table->boolean('newsletter_opt_in')->default(false);
            $table->boolean('privacy_accepted')->default(false);
            $table->enum('status', ['NEW', 'PROCESSED', 'SENT', 'CANCELLED'])->default('NEW');
            $table->string('robot_field')->nullable();
            $table->timestamps();
        });
    }
}
