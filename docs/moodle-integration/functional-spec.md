# Moodle Integration – Functional Specification

## 1. Purpose

This document defines the functional behaviour of the integration between **imakademia.hu** and Moodle.

The primary system of record for courses, applications and participant administration remains **imakademia.hu**.

Moodle is used as the learning platform receiving the course and participant data required for access to online training content.

The integration is divided into:

* course synchronization;
* participant and enrolment synchronization;
* optional Moodle-originated events.

---

# 2. Course Synchronization

## 2.1. Course representation

A Moodle course represents one concrete scheduled course instance in imakademia.hu.

If the same training type is offered at multiple dates, each scheduled instance must be represented by a separate Moodle course.

Example:

* Vállalati innovációmenedzsment szakértő – September 2026
* Vállalati innovációmenedzsment szakértő – January 2027

These must become two separate Moodle courses.

---

## 2.2. Creating a Moodle course

When a new course instance is created in imakademia.hu and becomes eligible for Moodle synchronization, the system creates the corresponding Moodle course.

The two records must be linked using a persistent identifier so later synchronization operations update the same Moodle course instead of creating another one.

The implementation must prevent accidental duplicate Moodle course creation.

---

## 2.3. Updating a course

When Moodle-relevant course information changes in imakademia.hu, the corresponding Moodle course must be updated.

Possible synchronized fields include:

* unique course identifier;
* course name;
* course classification/category;
* training location;
* start date;
* end date;
* participation mode;
* teaching days;
* start and end times of teaching days.

Only fields actually used by Moodle should be synchronized.

Possible additional fields include:

* application deadline;
* course fee;
* minimum participant count;
* maximum participant count.

These should only be transferred if a concrete Moodle-side use case is defined.

---

## 2.4. Course completion / closing

Reaching the course end date must not automatically delete the Moodle course.

Historical course and participant information should remain available.

The exact Moodle-side behaviour of a completed course must be finalized before implementation.

Possible behaviours include:

* hiding the Moodle course;
* keeping it available for previous participants;
* another Moodle-side archive or closing process.

---

## 2.5. Course deletion

Deleting or cancelling a synchronized course in imakademia.hu must not automatically result in permanent Moodle course deletion.

The preferred behaviour is to close, hide or deactivate the Moodle course where possible so historical learning and participant data are preserved.

Permanent Moodle deletion should only happen when explicitly required by an agreed business rule.

---

# 3. Participant Synchronization

## 3.1. New application

When a participant submits a course application, the application is initially stored in imakademia.hu with the current normal application workflow.

At this stage the participant is **not automatically sent to Moodle**.

This allows administrators to review and correct the submitted data before Moodle access is created.

---

## 3.2. Synchronization trigger

The currently proposed synchronization trigger is when an administrator changes the application to **Processed / Feldolgozott** status.

At that point the system must:

1. verify that the corresponding Moodle course exists;
2. find or create the Moodle user;
3. enrol the Moodle user in the relevant course.

If Moodle access should only be provided after payment, a separate business status such as **Paid / Fizetve** may be required instead.

This trigger must be finalized before implementation.

---

# 4. Moodle User Identification

The same person may participate in multiple courses.

The integration should therefore reuse an existing Moodle user instead of creating a new account for every application.

The initial proposed matching key is the participant's **email address**.

Expected behaviour:

* if a Moodle user with the relevant email address already exists, reuse that user;
* if no matching Moodle user exists, create a new user.

The suitability of email address as the primary matching identifier must be validated.

If the same person later applies using a different email address, the current imakademia.hu data model may not be able to reliably identify the applications as belonging to the same person.

In such a case a new Moodle account may be created.

---

# 5. Participant Data Sent to Moodle

Only participant data required for Moodle operation should be transferred.

Planned fields:

* first name;
* last name;
* email address;
* Moodle username and/or Moodle user identifier as required by the API.

The following data should not be transferred by default:

* telephone number;
* notification/postal address;
* payer information;
* billing information;
* other personal information not required by Moodle.

Educational identifiers or other additional personal data should only be transferred when there is a concrete educational, certification or legal requirement.

---

# 6. Updating Participant Data

When a participant has already been synchronized to Moodle and Moodle-relevant data changes in imakademia.hu, the corresponding Moodle data should also be updated.

Relevant examples:

* first or last name;
* email address.

Changes to data not used by Moodle must not trigger unnecessary synchronization.

Examples:

* payer information;
* billing data;
* unrelated administrative fields.

Special care is required when changing the email address because email is currently the proposed user matching identifier.

---

# 7. Course Enrolment

After the participant has been identified or created in Moodle, the user must be enrolled in the Moodle course associated with the application.

The integration must avoid duplicate enrolments when synchronization is retried or the same operation is triggered multiple times.

A Moodle user may be enrolled in multiple courses simultaneously.

Operations affecting one application must not modify access to unrelated courses.

---

# 8. Application Cancellation

When an application that has already been synchronized to Moodle is changed to **Cancelled / Lemondott**:

* the Moodle user account must not be deleted;
* access to unrelated Moodle courses must remain unchanged;
* only access to the affected course should be disabled;
* historical learning results and activity should be preserved where Moodle allows it.

The preferred Moodle-side behaviour is to **suspend the course enrolment** rather than delete the Moodle user.

The exact enrolment operation must be validated against the target Moodle installation.

---

# 9. Course Not Running Due to Minimum Participant Count

The decision that a course will not run because the minimum participant count was not reached is currently an administrative/manual business process.

The current A+B synchronization scope does not define an automatic rule for determining whether the minimum participant count has been reached.

If a course is manually marked as cancelled or not running, participants already synchronized to Moodle should have their access to that course removed or suspended.

Their Moodle user accounts must not be deleted because they may have access to other Moodle courses.

If automatic minimum-participant handling is required later, its exact ownership and workflow must be specified separately.

---

# 10. Maximum Participant Count and Application Deadline

Applications are submitted through imakademia.hu.

Any future automatic enforcement of:

* maximum participant count;
* application deadline;

belongs to the application/business workflow and must be explicitly specified if required.

Moodle should only receive participants who have reached the agreed synchronization status in imakademia.hu.

The current Moodle integration does not itself define new application-limit automation.

---

# 11. Communication Failures

Moodle communication must not block the normal operation of imakademia.hu.

If Moodle is temporarily unavailable or an API operation fails:

* the imakademia.hu operation must not be lost;
* the failed synchronization must be detectable;
* the synchronization must be retryable;
* retrying must not create duplicate courses, users or enrolments;
* synchronization errors should be visible to administrators where appropriate;
* manual retry should be possible where appropriate.

The integration must therefore be designed so that synchronization operations are idempotent.

---

# 12. API and Validation Errors

Errors returned directly by Moodle as a response to API calls initiated by imakademia.hu are part of the course or participant synchronization process.

Examples include:

* Moodle unavailable;
* authentication failure;
* validation failure;
* course not found;
* user already exists;
* invalid request data;
* enrolment failure;
* other Moodle API errors.

These belong to modules A and B and are **not** considered separate Moodle-originated events.

---

# 13. Existing Courses and Participants

When the integration is introduced, historical data does not necessarily need to be synchronized automatically.

The intended initial approach is:

* active courses may be synchronized;
* future courses may be synchronized;
* already processed participants belonging to those relevant courses may also be synchronized;
* old completed courses do not need automatic retrospective migration by default.

Before initial synchronization, a specific date or selection rule should be agreed to determine which existing records must be transferred.

---

# 14. Personal Data Deletion

Cancelling a course application is not the same as requesting deletion of personal data.

A personal data deletion request may require separate handling in both systems.

Before implementing automatic deletion behaviour, it must be determined:

* which data may be deleted from imakademia.hu;
* which data may or must be deleted from Moodle;
* which records are subject to retention obligations;
* whether the Moodle user has other active or historical course participation.

Deleting one application or one course enrolment must therefore not automatically delete the entire Moodle user account.

Legal and retention rules are outside the scope of this technical specification unless separately provided.

---

# 15. Moodle-Originated Events

A separate optional integration direction may be implemented for events that originate in Moodle and must be communicated back to imakademia.hu.

Potential examples include:

* Moodle-side enrolment status changes;
* Moodle-side user status changes;
* course completion events;
* course state changes;
* other Moodle-side events relevant to administration;
* participant-count-related feedback if such responsibility is ultimately implemented on the Moodle side.

The exact list of Moodle-originated events is **not yet finalized**.

This functionality must not be implemented based on assumptions.

The final event list and the expected behaviour for each event must be specified before implementation.

---

# 16. General Synchronization Rules

The following rules apply to the complete integration:

1. **imakademia.hu is the primary source of course and application data.**
2. Synchronization must be safe to retry.
3. Duplicate courses, users and enrolments must be avoided.
4. Existing Moodle entities should be reused where appropriate.
5. A Moodle outage must not prevent normal administration in imakademia.hu.
6. Normal application cancellation must never delete an entire Moodle user account.
7. Operations affecting one course must not affect access to unrelated courses.
8. Only data with a defined Moodle use case should be transferred.
9. Missing business rules must not be invented during implementation.
10. Behaviour depending on the actual Moodle installation must be verified against its available Web Service API.

---

# 17. Open Questions

The following items must be clarified or technically validated during implementation:

* Which exact application status triggers Moodle enrolment?
* Is **Processed / Feldolgozott** the final trigger, or should Moodle access depend on payment?
* Is email address acceptable as the primary Moodle user matching key?
* Which exact course fields exist in the target Moodle installation?
* Which data requires Moodle custom fields?
* How should completed courses be handled: visible, hidden, archived or otherwise?
* Which Moodle enrolment operation should be used for cancelled applications?
* Which existing courses and participants should be synchronized when the integration goes live?
* Which Moodle-originated events, if any, are required?
* Does any required functionality need a custom Moodle Web Service function or plugin?

These questions should be recorded and resolved rather than handled through undocumented implementation assumptions.
