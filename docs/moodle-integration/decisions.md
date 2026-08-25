# Moodle Integration Decisions

## 2026-08-14 - Initial course synchronization state

Store the initial Moodle course synchronization state directly on `actual_courses` using nullable fields for the Moodle course ID, sync status, last successful sync timestamp and last sync error.

Reason: the current course synchronization scope maps exactly one local scheduled course instance (`ActualCourse`) to one Moodle course, and the agreed implementation step explicitly keeps persistence minimal without dedicated mapping or history tables. A dedicated integration table can be reconsidered later if retry history, audit requirements or multi-system mappings become necessary.

## 2026-08-14 - Initial participant synchronization state

Store the initial Moodle participant synchronization state directly on `course_applications` using nullable fields for the Moodle user ID, sync status, last successful sync timestamp and last sync error.

Reason: the current participant synchronization step keeps persistence minimal and does not introduce separate Moodle user, enrolment, mapping, history or synchronization tables. Multiple applications may temporarily store the same Moodle user ID. A dedicated user/enrolment mapping table can be reconsidered later if cross-application identity handling, enrolment state history or audit requirements become necessary.

## 2026-08-21 - Moodle course identifiers and initial standard-field payload

One Moodle course represents one `ActualCourse`. After mapping, the persisted `actual_courses.moodle_course_id` is the primary identifier used for updates.

Before creating an unmapped course, look it up by the environment-namespaced, integration-owned deterministic Moodle `idnumber`:

```text
imakademia-{namespace}-actual-course-{actual_course_id}
```

Use this deterministic Moodle `shortname`:

```text
IMA-{NAMESPACE}-{actual_course_id}
```

`MOODLE_COURSE_NAMESPACE` is required for course synchronization. It is normalized to a lowercase ASCII hyphen-separated slug for `idnumber` and uppercased for `shortname`. For example, namespace `test` produces `imakademia-test-actual-course-5` and `IMA-TEST-5` for `ActualCourse` ID `5`. Environments sharing one Moodle installation must be configured with distinct namespace values after normalization.

Build `fullname` from the local course name and scheduled start date so separate scheduled instances remain distinguishable. An exact existing `idnumber` match is adopted and updated; an ambiguous match fails safely without creating a course.

The initial payload uses only verified standard Moodle fields: `fullname`, `shortname`, `idnumber`, `categoryid`, `startdate`, optional `enddate` and explicitly supplied `visible`. Category ID comes from explicit synchronization input or configuration, and visibility must be chosen by the caller because no production visibility rule has been agreed.

Do not initially transfer classification, place of event, participation mode or teaching-day structures because no target fields/use cases have been verified. Normal synchronization must never call `core_course_delete_courses`.

Reason: deterministic identifiers and lookup-before-create provide retry-safe duplicate prevention even when a previous successful Moodle creation was not persisted locally. Local database IDs can overlap across development, test and production environments that share a Moodle installation, so the namespace prevents one environment from finding or colliding with another environment's course. Limiting the payload to verified standard fields avoids inventing Moodle-side semantics.

The pre-namespace live smoke-test course (`IMA-5`, `imakademia-actual-course-5`) is retained as historical test data and is not automatically adopted or migrated by the namespaced synchronization flow.

## 2026-08-21 - Asynchronous automatic course synchronization

Automatic Moodle course synchronization is dispatched to the Laravel queue rather than performed inside local model persistence. Each job stores only the `ActualCourse` ID and reloads current data when it executes. Jobs for the same `ActualCourse` are serialized with Laravel's `WithoutOverlapping` queue middleware and a finite lock expiry.

Creating an `ActualCourse` dispatches synchronization when Moodle integration is enabled. Updating an `ActualCourse` dispatches only when `course_id`, `start_date` or `end_date` changes because these are the local fields currently represented by the verified Moodle payload. Moodle synchronization-state changes and unsupported course metadata do not recursively dispatch jobs.

When `Course::name` changes, synchronization is dispatched only for related `ActualCourse` records that have already entered the Moodle lifecycle, identified by a non-null Moodle course ID or synchronization status. This prevents a parent-course edit from backfilling unrelated historical scheduled courses.

`ActualCourseDay` changes do not trigger Moodle synchronization. Teaching-day data must first receive an agreed Moodle-side representation before it participates in payloads or automatic triggers. Moodle visibility is an explicit environment configuration value and is not inferred from `Course::is_active` or `listed`.

## 2026-08-23 - Course synchronization operational retry and administrator controls

`SyncMoodleCourse` uses Laravel queue retries with at most three attempts. Failed attempts are retried after an increasing backoff of 60 seconds and then 300 seconds. The existing per-course `WithoutOverlapping` middleware remains in place, and synchronization exceptions continue to propagate to Laravel's normal retry and failed-job handling.

Requesting synchronization is centralized in `CourseSyncDispatcher`. It sets `moodle_sync_status` to `pending` before dispatching the job after the current database transaction commits. A null status therefore means synchronization has never been requested, `pending` means it has been queued or is executing, `synced` means the latest completed attempt succeeded, and `failed` means the latest completed attempt failed. A retry marks the record pending again when execution begins; success clears the previous error. Moodle state-only updates do not trigger observers, avoiding recursive dispatch.

Administrators can request synchronization asynchronously from an `ActualCourse` edit page for never-synchronized, synchronized or failed records. The action uses the same dispatcher and queue job as automatic synchronization and is hidden when Moodle integration is disabled. The course listing shows only a compact status badge, while the edit page shows the Moodle course ID, status, last successful synchronization time and safe last error.

## 2026-08-23 - Participant synchronization MVP

`PROCESSED` is the participant enrolment trigger. Public `NEW` applications are not synchronized. After an application enters the Moodle lifecycle, first name, last name and email changes are synchronized; billing, payer, phone, address and synchronization-state changes are not triggers.

Initial user resolution uses an exact normalized email lookup through `core_user_get_users_by_field`. After a Moodle user ID is persisted on the application, that ID is authoritative: later email changes update the mapped user and never remap by the new email. Ambiguous or malformed lookup results fail without creating a user. The MVP Moodle username is the normalized lowercase participant email, generated by a dedicated strategy class. The target has an existing email-style username, which confirms that email-format usernames are accepted by its current username policy; the strategy remains replaceable.

New users use configurable authentication and password-generation settings, initially `manual` and `createpassword=true`. The integration does not generate or deliver passwords itself. The manual-enrolment student role ID is configuration, initially target role ID `5`, rather than service logic.

`enrol_manual_enrol_users` is used idempotently with `suspend=0` for initial enrolment and reactivation, and `suspend=1` for cancellation. Cancellation never deletes the user, never calls unenrolment and changes only the mapped user/course enrolment. Returning a cancelled application to `PROCESSED` reactivates that enrolment. An unmapped cancellation is a successful no-op and does not create a user or course.

Participant synchronization follows module A's queue policy: pending is recorded at dispatch, jobs have three attempts with 60- and 300-second backoff, per-application overlap protection, safe failed-state diagnostics and normal Laravel failed-job handling. Manual synchronization uses the same dispatcher/job and is available only for `PROCESSED` or `CANCELLED` applications while Moodle integration is enabled.
