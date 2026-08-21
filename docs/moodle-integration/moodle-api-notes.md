# Moodle Integration – API Notes

## 1. Purpose

This document contains technical notes for the Moodle Web Service integration used by **imakademia.hu**.

It is intended to document:

* Moodle Web Service configuration;
* authentication;
* available API functions;
* request and response conventions;
* required capabilities;
* course and user field mappings;
* enrolment behaviour;
* technical limitations discovered during implementation.

This document must be updated when access to the target Moodle installation becomes available.

The **target Moodle installation's generated Web Service API documentation is authoritative**.

Do not assume that every standard Moodle Web Service function is enabled for the integration user.

---

# 2. Target Moodle Environment

Current known target:

* Moodle installation: `elearning.imakademia.hu`
* Web Service API documentation:
  `https://elearning.imakademia.hu/admin/webservice/documentation.php`
* Moodle version: **Moodle 4.5.11 (build identifier `2024100711`)**. The build identifier is exposed by the target's public Moodle Mobile download link. This should still be cross-checked against `core_webservice_get_site_info.version` when the integration token is configured.
* REST endpoint: `https://elearning.imakademia.hu/webservice/rest/server.php`
* REST Web Services enabled: **verified administratively; endpoint is reachable**
* Generated Web Service documentation: enabled, but authentication is required; unauthenticated access redirects to `/login/index.php`
* Web Service name: **not yet retrievable from the repository environment**
* Web Service shortname: **not yet retrievable from the repository environment**
* Integration user: `webservicer`, Moodle user ID `14` (**verified against the current live token**)
* Integration role: `webservice-user` (provided target configuration)
* Authentication: dedicated Web Service token associated with the custom External Service; the token must remain local and secret

Investigation on 2026-08-21 found that the checked-out application's local environment did not contain `MOODLE_BASE_URL` or `MOODLE_WEB_SERVICE_TOKEN`, and Moodle integration was disabled. Consequently, token-scoped service metadata and the generated per-service function documentation could not be retrieved. In particular, no function is classified below as assigned to the custom service until `core_webservice_get_site_info` or the authenticated generated documentation confirms it.

The next target-specific investigation step is a read-only call to `core_webservice_get_site_info`. Its `functions[]` result is the authoritative list available to the token. The authenticated documentation must then be used to capture the service name/shortname and verify the parameter descriptions against the installed build.

---

# 3. Moodle Web Service Architecture

Moodle provides an External Services / Web Service framework which allows external systems to invoke exposed Moodle functions.

The integration is expected to use the **REST protocol**.

A typical REST request is sent to:

```text
/webservice/rest/server.php
```

with parameters including:

```text
wstoken=<TOKEN>
wsfunction=<FUNCTION_NAME>
moodlewsrestformat=json
```

Function-specific parameters are included in the same request.

JSON should be used as the response format unless a concrete reason requires otherwise.

The Moodle REST interface accepts GET/POST parameters, but application integration should use HTTP POST for normal API operations.

---

# 4. Authentication

The preferred integration mechanism is a dedicated Moodle Web Service token associated with an appropriate integration user/service.

Expected application configuration should resemble:

```env
MOODLE_BASE_URL=
MOODLE_WEB_SERVICE_TOKEN=
```

The token must:

* never be committed to the repository;
* be stored using the application's normal secret/environment configuration;
* belong to a user with only the capabilities required by this integration.

Do not implement username/password login or dynamic token generation unless the target Moodle configuration specifically requires it.

The exact authentication configuration must be verified against the target Moodle installation.

---

# 5. External Service Configuration

The Moodle administrator may expose a custom External Service containing only the functions required by imakademia.hu.

The integration user must have:

* access to the configured External Service;
* permission to use REST Web Services;
* all capabilities required by the individual functions exposed through the service.

The actual enabled functions and their required capabilities must be obtained from the target Moodle Web Service documentation.

---

# 6. Candidate Web Service Functions

The following functions are standard Moodle Web Service functions which appear relevant to the current functional specification.

**These are candidates only.**

Before implementation, verify that they:

1. exist in the target Moodle version;
2. are exposed by the configured External Service;
3. accept the parameters required by the integration;
4. can be called by the integration user.

---

## 6.1. Course functions

### `core_course_create_courses`

Purpose:

Create Moodle courses.

Expected use:

```text
imakademia.hu course
        ↓
core_course_create_courses
        ↓
Moodle course
```

Potential use cases:

* initial synchronization of a new course;
* controlled initial synchronization of existing active/future courses.

Fields and required parameters must be verified against the target site's API documentation.

---

### `core_course_update_courses`

Purpose:

Update existing Moodle courses.

Expected use:

* course name changes;
* start/end date changes;
* relevant Moodle course metadata changes;
* other synchronized fields supported by the Moodle API.

Do not recreate a course when a linked Moodle course already exists.

---

## 6.2. User lookup

### `core_user_get_users_by_field`

Purpose:

Retrieve Moodle users using a specific field.

This is the preferred candidate for exact user lookup.

The current functional specification proposes matching existing Moodle users by:

```text
email
```

Expected flow:

```text
participant email
      ↓
core_user_get_users_by_field
      ↓
0 users → create Moodle user
1 user  → reuse Moodle user
```

Behaviour for unexpected multiple matches must be investigated and handled explicitly rather than guessed.

---

### `core_user_get_users`

Purpose:

General Moodle user search.

This may be useful if exact lookup through `core_user_get_users_by_field` cannot satisfy a requirement.

Prefer exact field-based lookup where possible.

---

## 6.3. User creation

### `core_user_create_users`

Purpose:

Create Moodle users.

Expected minimum integration data currently includes:

* username;
* first name;
* last name;
* email address.

The target Moodle installation may require additional fields.

The username-generation strategy is currently **not finalized** and must not be invented silently during implementation.

Before implementation verify:

* required fields;
* username requirements;
* password/authentication requirements;
* authentication plugin used by created users;
* whether Moodle can generate or manage credentials according to the desired workflow.

---

## 6.4. User update

### `core_user_update_users`

Purpose:

Update an existing Moodle user.

Expected use:

* first-name changes;
* last-name changes;
* email-address changes;
* other explicitly synchronized participant fields.

Email changes require special care because email is currently proposed as the primary user matching key.

The Moodle user ID should therefore be persisted after the initial successful mapping and reused for later updates wherever possible.

---

# 7. Course Enrolment

## 7.1. Manual enrolment

Candidate function:

### `enrol_manual_enrol_users`

Purpose:

Enrol an existing Moodle user into a course through Moodle's manual enrolment mechanism.

Expected flow:

```text
imakademia.hu application reaches synchronization status
                ↓
       resolve Moodle course
                ↓
        resolve Moodle user
                ↓
     enrol_manual_enrol_users
```

The target course must have the necessary manual enrolment method available.

The following must be verified:

* student role ID;
* enrolment plugin configuration;
* course enrolment requirements;
* start/end timestamps if required;
* behaviour when the user is already enrolled.

Retrying synchronization must not create an invalid or duplicate enrolment state.

---

# 8. Cancelling / Suspending Access

The functional specification currently prefers **suspending the affected course enrolment** rather than deleting the Moodle user.

Potential standard Moodle functions relevant to this workflow include:

### `core_enrol_edit_user_enrolment`

Candidate for changing an existing enrolment state.

### `enrol_manual_unenrol_users`

Candidate for removing a manual course enrolment.

The preferred implementation is **not yet decided**.

Before choosing between suspension and unenrolment, investigate:

* whether course history/results remain available;
* whether previous grades/activity are preserved;
* whether suspended enrolments can be restored cleanly;
* actual business expectations;
* capabilities exposed by the target Moodle service.

Do not delete the Moodle user when cancelling a single application.

---

# 9. Moodle Entity Mapping

The integration should persist Moodle identifiers after successful synchronization.

Expected mappings include:

```text
imakademia.hu course ID
        ↔
Moodle course ID
```

and:

```text
imakademia.hu participant/application
        ↔
Moodle user ID
```

The exact database design must be determined after investigating the existing imakademia.hu architecture.

Do not rely solely on names or email addresses for entities that have already been successfully mapped.

Persistent Moodle IDs should be preferred for subsequent operations.

---

# 10. Course Fields

The functional specification currently identifies the following possible course data:

* unique course identifier;
* course name;
* course category/classification;
* location;
* start date;
* end date;
* participation mode;
* teaching days;
* teaching-day start/end times.

Not all of these necessarily map to standard Moodle course fields.

Once target Moodle access is available, classify every field as one of:

```text
STANDARD_MOODLE_FIELD
CUSTOM_FIELD
NOT_REQUIRED_BY_MOODLE
REQUIRES_CUSTOM_IMPLEMENTATION
```

Do not introduce custom Moodle fields or custom Moodle Web Service functions until this mapping is completed.

---

# 11. Participant Fields

Current planned Moodle participant data:

* first name;
* last name;
* email address;
* Moodle username;
* Moodle user ID.

Potentially excluded unless explicitly required:

* telephone number;
* address;
* billing information;
* payer information;
* unrelated personal/administrative information.

The exact Moodle field mapping must be documented here once verified.

---

# 12. Error Responses

Moodle API calls can fail because of:

* authentication/authorization errors;
* invalid parameters;
* missing required fields;
* missing capabilities;
* unavailable Web Service functions;
* Moodle-side validation;
* unavailable Moodle service;
* unexpected server errors.

The application must not treat every Moodle failure as an application-level fatal error.

Synchronization failures should be recorded and made retryable according to the integration architecture.

The exact Moodle exception/response structure should be documented here after the first API calls against the target environment.

Example section to complete later:

```text
Function:
core_course_create_courses

Failure:
TODO

Moodle response:
TODO

Application handling:
TODO
```

---

# 13. Idempotency

Moodle synchronization must be designed to safely tolerate retries.

Before performing creation operations:

### Courses

Prefer an already persisted Moodle course mapping.

Do not create another Moodle course merely because a retry occurs.

### Users

Prefer a persisted Moodle user ID where available.

Otherwise perform the agreed exact user lookup before creating a new user.

### Enrolments

Check or otherwise safely handle an already existing enrolment.

Retrying a successful synchronization must leave the Moodle state logically unchanged.

---

# 14. Custom Moodle Web Service Functions

Moodle allows additional Web Service functions to be implemented when the standard exposed API does not provide the required behaviour.

Custom Moodle-side implementation should **not** be the default approach.

Use existing Moodle functions where they adequately support the integration.

Consider a custom Moodle function/plugin only when:

* required data cannot be represented through the existing API;
* multiple Moodle-side operations require specific atomic/business behaviour;
* a required Moodle-originated workflow cannot be implemented using standard functionality;
* the target Moodle configuration otherwise requires it.

Any custom Moodle development must be documented here and in `decisions.md`.

---

# 15. Moodle-Originated Events

No concrete technical implementation has yet been selected for Moodle → imakademia.hu events.

Do not implement polling, webhooks, event observers or custom plugins until the required event list is finalized.

Potential events are documented in `functional-spec.md`.

Once requirements are known, document here:

* Moodle event name;
* event source;
* payload;
* delivery mechanism;
* authentication;
* retry behaviour;
* imakademia.hu endpoint;
* duplicate-event handling.

---

# 16. First Access Investigation Checklist

When target Moodle access is provided, perform an investigation before implementing the integration.

Verify:

* Moodle version;
* REST Web Services enabled;
* integration service name;
* available functions;
* integration user;
* token/authentication mechanism;
* required capabilities;
* course categories;
* standard course fields;
* configured course custom fields;
* required participant fields;
* authentication method for Moodle students;
* manual enrolment availability;
* student role ID;
* enrolment suspension behaviour;
* generated Web Service documentation;
* relevant API request/response structures.

Then update this document with the actual findings.

---

# 17. Candidate Function Summary

Current candidate standard functions:

```text
Course
------
core_course_create_courses
core_course_update_courses

Users
-----
core_user_get_users_by_field
core_user_get_users
core_user_create_users
core_user_update_users

Enrolment
---------
enrol_manual_enrol_users
core_enrol_edit_user_enrolment
enrol_manual_unenrol_users
```

This list is **not an implementation contract**.

The final list must be based on the functions actually exposed by the target Moodle Web Service.

---

# 18. Open Technical Questions

The following questions remain unresolved until target Moodle access is available:

* What Moodle version is running?
* Which standard Web Service functions are exposed?
* Which functions must be added to the External Service?
* What permissions/capabilities does the integration user have?
* Which Moodle course category should synchronized courses use?
* Which course data maps to standard fields?
* Which data requires custom fields?
* What username format should newly created users use?
* How are Moodle user passwords/authentication handled?
* Is email guaranteed to be suitable as the initial user lookup field?
* Is manual enrolment enabled for the target courses?
* What is the Moodle student role ID?
* Should cancelled applications suspend or remove enrolment?
* How should completed/closed courses be represented in Moodle?
* Are any custom Moodle Web Service functions necessary?
* Which Moodle-originated events, if any, require implementation?

These questions must be investigated rather than resolved through undocumented assumptions.

---

# 19. Target Capability Investigation (2026-08-21)

## 19.1. Verification boundary

No write function was called. No course, category, user or enrolment data was created, changed or deleted.

The target site and REST endpoint are reachable, and the generated documentation is protected by Moodle login. The repository environment did not contain a token or an authenticated Moodle session. Therefore:

* the target's custom External Service name and shortname remain unverified;
* the functions currently assigned to that service remain unverified;
* the integration user's effective capabilities remain unverified;
* target categories, role IDs, authentication choices, custom fields and manual-enrolment instances remain unverified.

The function definitions below are verified against the Moodle 4.5 stable core source and identify standard functionality present in this Moodle release. They are **not evidence that the functions are currently assigned to the target custom External Service**.

## 19.2. Standard course functions in Moodle 4.5

### `core_course_create_courses`

Request: `courses[]` containing required `fullname`, `shortname`, `categoryid`; optional `idnumber`, `summary`, `summaryformat`, `format`, `showgrades`, `newsitems`, `startdate`, `enddate`, deprecated `numsections`, `maxbytes`, `showreports`, `visible`, `hiddensections`, `groupmode`, `groupmodeforce`, `defaultgroupingid`, `enablecompletion`, `completionnotify`, `lang`, `forcetheme`, `courseformatoptions[]` (`name`, `value`) and `customfields[]` (`shortname`, `value`). Dates are Unix timestamps.

Response: an array with one item per created course: `id` and `shortname`.

Relevant capability: `moodle/course:create` in the selected category context. Visibility and some optional settings can require additional capabilities such as `moodle/course:visibility` and `moodle/course:setforcedlanguage`.

### `core_course_update_courses`

Request: `courses[]` containing required Moodle course `id`, plus any supported values to update: `fullname`, `shortname`, `categoryid`, `idnumber`, `summary`, `summaryformat`, `format`, `showgrades`, `newsitems`, `startdate`, `enddate`, deprecated `numsections`, `maxbytes`, `showreports`, `visible`, `hiddensections`, `groupmode`, `groupmodeforce`, `defaultgroupingid`, `enablecompletion`, `completionnotify`, `lang`, `forcetheme`, `courseformatoptions[]` (`name`, `value`) and `customfields[]` (`shortname`, `value`).

Response: `null` on success.

Relevant capabilities depend on changed fields and context, principally `moodle/course:update`; moving category and changing visibility/language require their corresponding capabilities.

The standard `visible` field supports hiding (`0`) and showing (`1`) through create/update; no custom hide/show function is required.

### Retrieval and search

* `core_course_get_courses`: request optional `options[ids][]`; response is an array of course records including identifiers, names, category, visibility, dates, summary/format and other course settings visible to the caller.
* `core_course_get_courses_by_field`: request optional `field` and `value`. Supported exact fields are `id`, `ids`, `shortname`, `idnumber` and `category`; an empty field returns all courses visible to the caller. Response: `courses[]` plus `warnings[]`.
* `core_course_search_courses`: request `criterianame` (`search`, `modulelist` or `blocklist`), `criteriavalue`, optional `page`, `perpage`, and optional `requiredcapabilities[]` and `limittoenrolled`. Response: `total`, `courses[]`, `warnings[]`.

For idempotent integration, a persisted Moodle ID remains preferred. A unique local identifier may also be placed in standard `idnumber` and queried exactly with `core_course_get_courses_by_field`.

### Deletion

`core_course_delete_courses` is standard. Request: `courseids[]` of Moodle course IDs. Response: `warnings[]`. Relevant capability: `moodle/course:delete` in each course context. The functional specification does not permit automatic permanent deletion, so exposure is not required for the planned normal workflow.

### Categories

* `core_course_get_categories`: request optional `criteria[]` (`key`, `value`) and optional `addsubcategories`; response is an array of category records including `id`, `name`, `idnumber`, `description`, `parent`, `sortorder`, `coursecount`, `visible`, `visibleold`, `timemodified` and `depth` where permitted.
* `core_course_create_categories`: request `categories[]` with required `name`; optional `parent`, `idnumber`, `description`, `descriptionformat`, `theme`. Response includes created `id` and `name`.
* `core_course_update_categories`: request `categories[]` with required `id` and optional mutable category fields; response is `null`.
* `core_course_delete_categories`: destructive category management is standard but is not needed for this integration.

Only category lookup is known to be required. Category creation/management should not be exposed unless a separate business rule assigns ownership of Moodle categories to imakademia.hu.

## 19.3. Standard user functions in Moodle 4.5

### Exact lookup: `core_user_get_users_by_field`

Request: required `field` and `values[]`. Supported fields are `id`, `idnumber`, `username` and `email`. For the proposed exact email lookup use `field=email` and one normalized email in `values[]`.

Response: an array of user records. The user description can include `id`, `username`, `firstname`, `lastname`, `fullname`, `email`, `department`, `firstaccess`, `lastaccess`, `auth`, `suspended`, `confirmed`, `lang`, `theme`, `timezone`, `mailformat`, profile URLs, custom fields, preferences and other fields allowed by the caller's capabilities/privacy rules.

Email lookup is technically supported. Target uniqueness policy and the handling of zero or multiple results still require confirmation; Moodle's create-user API describes email as valid and unique, but site configuration can affect duplicate-email policy.

### General search: `core_user_get_users`

Request: `criteria[]` (`key`, `value`). Response: `users[]` and `warnings[]`. This is a broad search and is not preferred for identity matching when exact field lookup is available.

### Creation: `core_user_create_users`

Request: `users[]`. Required fields are:

* `username` (subject to the site's username policy);
* `firstname` (non-blank);
* `lastname` (non-blank);
* `email` (valid; described by the API as unique).

Authentication/credential fields:

* `auth` defaults to `manual`;
* `password` is optional at the external schema level;
* optional `createpassword=true` asks Moodle to create a password and mail it to the user;
* whether password omission is valid depends on `auth`, `createpassword`, the enabled authentication plugin and target policy. The integration must not choose this workflow until verified on the target.

Other optional fields include `idnumber`, `lang`, `calendartype`, `theme`, `timezone`, `mailformat`, `description`, `city`, `country`, `firstnamephonetic`, `lastnamephonetic`, `middlename`, `alternatename`, preferences and custom fields. Target user-profile custom fields may introduce additional mandatory values and must be checked in the authenticated generated documentation/UI.

Response: an array containing created user `id` and `username`.

Relevant capability: `moodle/user:create` at system context.

### Update and deletion

* `core_user_update_users`: request `users[]`, each with required Moodle `id` and optional mutable user fields including `username`, `firstname`, `lastname`, `email`, `auth`, `password`, `idnumber`, `suspended`, preferences and custom fields. Response: `null`. Relevant capability: `moodle/user:update` (with restrictions preventing inappropriate updates of privileged users).
* `core_user_delete_users`: request `userids[]`; response `null`; relevant capability `moodle/user:delete`. It is standard but not required for normal cancellation or enrolment lifecycle and should not be exposed merely for those workflows.

## 19.4. Standard manual enrolment functions in Moodle 4.5

### `enrol_manual_enrol_users`

Request: `enrolments[]`, each containing required `roleid`, `userid`, `courseid`; optional Unix timestamps `timestart`, `timeend`; optional `suspend` (`1` suspended, otherwise active).

Response: `null` on success. The batch is processed transactionally where supported and stops at the first error.

Requirements:

* the manual enrolment plugin must be enabled;
* an enabled manual enrolment instance must exist in the target course;
* the caller needs `enrol/manual:enrol` in the course;
* `roleid` must be assignable by the caller in that course.

The role ID is installation-specific and is **not verified**. It must be obtained from the target role configuration; it must not be assumed to equal a common default student role ID.

Moodle's manual enrolment plugin updates an existing manual user enrolment rather than inserting a second enrolment for the same manual instance/user. This makes a repeated identical call logically idempotent, although the integration should still reuse persisted IDs and verify state.

Passing `suspend=1` supports suspension during enrolment/update, and repeating with `suspend=0` supports reactivation. This standard function is preferable to `core_enrol_edit_user_enrolment`, which is deprecated in Moodle 4.5.

### `enrol_manual_unenrol_users`

Request: `enrolments[]`, each containing required `userid` and `courseid`; optional `roleid` is deprecated and ignored. Response: `null` on success. The caller needs `enrol/manual:unenrol` and the course needs the applicable manual enrolment instance.

Unenrolment removes the affected manual course enrolment, not the Moodle user and not enrolments in unrelated courses. Because the functional specification prefers preserving course history, suspension should be evaluated before choosing unenrolment.

### Read support for verification

`core_enrol_get_enrolled_users` can retrieve participants for a course and supports options including `onlyactive`, `onlysuspended`, requested user fields, limits and sorting. It requires course participant/enrolment review visibility capabilities for the requested detail. It can support safe verification of current enrolment state if assigned to the service.

Target-specific existing-enrolment, suspend/reactivate and history-preservation behaviour must still be confirmed against a non-production test course or through administrative inspection; no enrolment mutation was performed during this investigation.

## 19.5. `MoodleCoursePayloadMapper` field mapping

| Internal payload value | Standard Moodle representation | Classification / note |
| --- | --- | --- |
| `local_actual_course_id` | `idnumber` is technically suitable; persisted `actual_courses.moodle_course_id` remains the primary mapping after creation | Standard field; exact naming/uniqueness convention needs a decision |
| `user_given_id` | Could be `idnumber`, but only one value can occupy that standard field | Standard field candidate; conflicts with `local_actual_course_id`, so business meaning/precedence must be decided |
| `course_name` | `fullname`; a distinct required `shortname` must also be generated/provided | Direct standard mapping for full name; shortname rule remains open |
| `category.id`, `category.name` | Create/update requires Moodle `categoryid`; local category ID is not a Moodle category ID. Name can be used only to resolve a Moodle category | Requires explicit local-to-Moodle category mapping or agreed lookup rule; no custom development inherently required |
| `classification` | No matching standard course property | Course custom field, existing taxonomy/structure if one is already configured for this purpose, or omit if Moodle has no use case |
| `place_of_event` | No dedicated standard course property | Course custom field, course summary by an explicit presentation decision, or omit |
| `start_date` | `startdate` Unix timestamp | Direct standard mapping |
| `end_date` | `enddate` Unix timestamp | Direct standard mapping |
| `way_of_participation` | No dedicated standard course property | Course custom field, category/grouping only if an existing Moodle structure semantically owns this value, or omit |
| `teaching_days[].day/start_time/end_time` | No standard course field represents a structured list of teaching sessions | Course custom field(s) can store a display/serialized value; queryable structured sessions or Moodle-side behaviour would require custom Moodle development or another verified existing Moodle structure |
| course visibility/closed state (not currently emitted) | `visible` | Standard field; business rule for when to hide/show remains unresolved |

`customfields[]` in standard create/update accepts only target course custom fields already configured and editable by the integration user. The target's configured custom-field definitions and shortnames are not yet verified. No fields or plugin should be created until a Moodle-side use case and storage/query requirements are agreed.

## 19.6. Exposure classification pending token inspection

The following standard functions are likely required by the agreed course/participant scope and should be checked first in `core_webservice_get_site_info.functions[]`:

```text
core_webservice_get_site_info
core_course_create_courses
core_course_update_courses
core_course_get_courses_by_field
core_course_get_categories
core_user_get_users_by_field
core_user_create_users
core_user_update_users
enrol_manual_enrol_users
core_enrol_get_enrolled_users
enrol_manual_unenrol_users
```

This is a required-function checklist, not the currently exposed list. After token inspection each item must be marked either `ASSIGNED`, `STANDARD_BUT_NEEDS_SERVICE_ASSIGNMENT`, or `NOT_AVAILABLE_ON_TARGET`.

Normally unnecessary/destructive functions should not be added without a specific rule: `core_course_delete_courses`, category create/update/delete functions, and `core_user_delete_users`.

## 19.7. Custom Moodle-side development assessment

No custom function is yet proven necessary for basic course create/update/lookup, exact email user lookup, user create/update, manual enrolment, suspension/reactivation or unenrolment; Moodle 4.5 has standard functions for those operations.

Custom Moodle development may be required only if Moodle must consume the structured teaching-day schedule as structured/queryable data or execute Moodle-side behaviour from it. Simple display/storage could instead use configured course custom fields. Moodle-originated events remain unspecified and could require a plugin later, but are outside this investigation's implementation scope.

## 19.8. Remaining blockers and questions

1. Configure the local base URL/token (without committing either) and enable the integration, or provide an authenticated documentation export.
2. Call only `core_webservice_get_site_info` and record exact version, service-visible functions and site metadata.
3. Record the custom External Service name/shortname and compare its assignments with section 19.6.
4. Verify the `webservice-user` role's effective capabilities for each assigned function.
5. Identify the Moodle category/category mapping used for synchronized courses.
6. Identify the assignable student role ID and confirm manual enrolment instances on intended courses.
7. Decide the username and authentication/password-delivery workflow for new participants.
8. Confirm whether the target permits duplicate email addresses and approve email as the initial lookup key.
9. Inspect configured course and user custom fields, including mandatory user profile fields.
10. Decide Moodle course shortname and `idnumber` rules, including whether `local_actual_course_id` or `user_given_id` owns `idnumber`.
11. Decide course closing visibility and cancellation suspension versus unenrolment behaviour.

---

# 20. Target OpenAPI Inspection (2026-08-21)

## 20.1. What `openapi.json` represents

The checked-in document is an OpenAPI 3.1 definition generated by the target Moodle installation for Moodle's newer routed REST API:

* title: `Moodle LMS`;
* version/build identifier: `2024100711`;
* server: `https://elearning.imakademia.hu/r.php/api/rest/v2`;
* authentication schemes: an `api_key` request header and a `MoodleSession` cookie;
* inventory: three paths, five HTTP operations and two reusable schemas.

It is **not** an OpenAPI representation of the legacy Moodle External Service used by the current application design. Evidence:

* the configured legacy client endpoint is `/webservice/rest/server.php`, while this document describes `/r.php/api/rest/v2`;
* legacy calls use `wstoken`, `wsfunction` and `moodlewsrestformat`, none of which appears in the document;
* no legacy external-function name such as `core_course_create_courses` appears as an operation/path;
* the document contains no External Service name, shortname, integration user or function-assignment metadata;
* its declared operations are router-style resource paths for user preferences and templates.

It therefore appears to be the generated definition of the target's currently OpenAPI-declared **new REST v2 router routes**, which is broader/different in mechanism from the imakademia.hu custom External Service. It cannot establish which legacy functions are assigned to that service. It is also not a complete definition of every Moodle Web Service capability: the small route inventory omits the established External API function catalogue.

Absence from this file means only `NOT PRESENT IN OPENAPI`. It does not mean the function is absent from Moodle 4.5, unavailable through the legacy REST endpoint or unassigned to the custom External Service.

## 20.2. Complete operation inventory in the file

The file declares only these operations:

| HTTP operation | Purpose | Parameters/body | Response |
| --- | --- | --- | --- |
| `GET /user/{user}/preferences/{preference}` | Fetch one preference | Required path `user`; required string path `preference` | `200`: object with arbitrary string values; `400` invalid parameter; `404` not found |
| `POST /user/{user}/preferences/{preference}` | Set one preference | Same required path values; optional JSON body `{ "value": string }` | Same response shape |
| `GET /user/{user}/preferences` | Fetch all preferences | Required path `user` | Same response shape |
| `POST /user/{user}/preferences` | Set/update multiple preferences | Required path `user`; optional JSON body `{ "preferences": { <name>: string } }` | Same response shape |
| `GET /core/templates/{themename}/{component}/{identifier}` | Fetch a template | Required path `themename`, `component`, `identifier`; optional boolean query `includecomments`; optional `language` header | `200`: object with `templates` and `strings`, both string maps; `400`/`404` errors |

The `user` path identifier accepts `current`, a numeric user ID, `idnumber:<value>` or `username:<value>`. It does not accept email and these preference routes do not return user identity records. The template route explicitly has `security: []`; the preference routes inherit the global API-key-and-cookie declaration.

None of the five operations performs a course, category, user-account or enrolment synchronization operation.

## 20.3. Candidate legacy-function classification

| Expected Moodle function | OpenAPI classification | Explanation |
| --- | --- | --- |
| `core_course_create_courses` | `NOT PRESENT IN OPENAPI` | No course-create route or schema |
| `core_course_update_courses` | `NOT PRESENT IN OPENAPI` | No course-update route or schema |
| `core_course_get_courses` | `NOT PRESENT IN OPENAPI` | No course retrieval route |
| `core_course_get_courses_by_field` | `NOT PRESENT IN OPENAPI` | No exact course lookup route |
| `core_course_search_courses` | `NOT PRESENT IN OPENAPI` | No course search route |
| `core_course_delete_courses` | `NOT PRESENT IN OPENAPI` | No course deletion route |
| `core_course_get_categories` | `NOT PRESENT IN OPENAPI` | No category route |
| `core_course_create_categories` | `NOT PRESENT IN OPENAPI` | No category route |
| `core_course_update_categories` | `NOT PRESENT IN OPENAPI` | No category route |
| `core_course_delete_categories` | `NOT PRESENT IN OPENAPI` | No category route |
| `core_user_get_users_by_field` | `NOT PRESENT IN OPENAPI` | Preference routes can address a user by ID/idnumber/username but do not look up users or support email |
| `core_user_get_users` | `NOT PRESENT IN OPENAPI` | No user search route |
| `core_user_create_users` | `NOT PRESENT IN OPENAPI` | No user creation route/schema |
| `core_user_update_users` | `NOT PRESENT IN OPENAPI` | Preference mutation is not user-account update |
| `core_user_delete_users` | `NOT PRESENT IN OPENAPI` | No user deletion route |
| `enrol_manual_enrol_users` | `NOT PRESENT IN OPENAPI` | No enrolment route/schema |
| `enrol_manual_unenrol_users` | `NOT PRESENT IN OPENAPI` | No enrolment route/schema |
| `core_enrol_edit_user_enrolment` | `NOT PRESENT IN OPENAPI` | No enrolment update route/schema |
| `core_enrol_get_enrolled_users` | `NOT PRESENT IN OPENAPI` | No enrolment inspection route/schema |

No relevant candidate can be classified `AVAILABLE` or `NOT AVAILABLE` from this document. Whether each is assigned to the imakademia.hu External Service is `CANNOT BE DETERMINED FROM THIS FILE` even though its literal presence classification is `NOT PRESENT IN OPENAPI`.

## 20.4. Course field mapping impact

`openapi.json` contains no course create/update operation and no course schema. It therefore verifies **none** of the accepted parameters for course synchronization and does not supersede the Moodle 4.5 core-source findings in section 19.

| Local mapper field / need | Result from this OpenAPI file |
| --- | --- |
| `course_name` / Moodle `fullname` | `NOT YET DECIDABLE` from this file |
| `local_actual_course_id`, `user_given_id` / `idnumber` | `NOT YET DECIDABLE` from this file |
| required Moodle `shortname` | `NOT YET DECIDABLE` from this file |
| local category / Moodle `categoryid` | `NOT YET DECIDABLE` from this file; there is no category lookup/management route |
| `start_date`, `end_date` | `NOT YET DECIDABLE` from this file |
| visibility/hide/show | `NOT YET DECIDABLE` from this file |
| `classification` | `NOT YET DECIDABLE` from this file |
| `place_of_event` | `NOT YET DECIDABLE` from this file |
| `way_of_participation` | `NOT YET DECIDABLE` from this file |
| teaching days and start/end times | `NOT YET DECIDABLE` from this file |
| course custom fields | `NOT YET DECIDABLE` from this file; no `customfields` schema is declared |

The prior classifications remain the best technical candidates: `fullname`, `shortname`, `idnumber`, `categoryid`, `startdate`, `enddate` and `visible` are standard legacy Moodle fields; `customfields[]` is supported by the Moodle 4.5 legacy create/update functions; and structured teaching sessions have no standard course property. Those conclusions come from Moodle 4.5 core definitions, **not** from this OpenAPI file, and target External Service exposure still requires token documentation.

## 20.5. User requirements impact

The file has no user creation, update, deletion, search or exact-email lookup schema. It provides no evidence about:

* required `username`, `firstname`, `lastname` or `email` values;
* username policy;
* `password` versus `createpassword`;
* default or allowed `auth` plugins;
* email uniqueness;
* user custom fields or mandatory profile fields.

Its only user-related feature is preference access. The path can identify a user by numeric ID, `idnumber` or `username` (or `current`), but not by email, and it returns preferences rather than a Moodle user record. Section 19.3's creation requirements remain based on Moodle 4.5 core source and require target/token verification.

## 20.6. Enrolment capability impact

The file contains no enrolment operation or schema. It verifies none of `roleid`, `userid`, `courseid`, `suspend`, `timestart` or `timeend`; it does not document repeated-enrolment behaviour, suspension/reactivation, unenrolment or existing-enrolment inspection.

Section 19.4's findings remain valid as Moodle 4.5 standard-function behaviour, but availability to the imakademia.hu service cannot be inferred from this OpenAPI definition.

## 20.7. Live-token questions remaining

The OpenAPI inspection does not remove the main target-specific blocker. A read-only `core_webservice_get_site_info` call or authenticated legacy Web Service documentation export is still required to determine:

1. custom External Service name and shortname;
2. exact functions assigned to the integration token;
3. effective capabilities of `webservicer` / `webservice-user`;
4. target category IDs and category mapping;
5. assignable student role ID and manual-enrolment-instance availability;
6. configured course/user custom fields and mandatory user profile fields;
7. target authentication/password-delivery policy and duplicate-email policy;
8. actual legacy function documentation for installed build `2024100711`.

The new REST v2 API's `api_key` authentication relationship to the legacy Web Service token is also not stated in the file and should not be assumed.

## 20.8. Custom-development conclusion

This file does not make custom development newly necessary; it documents the wrong API surface for the planned legacy External Service integration. Moodle 4.5 standard legacy functions still appear sufficient for ordinary course/user/manual-enrolment synchronization if assigned and permitted.

Structured/queryable teaching-day storage or Moodle-side behaviour remains the only current course-data area that may require custom fields, an existing Moodle structure or custom Moodle development. The OpenAPI file contains no route that changes that assessment.

---

# 21. Superseded Mobile-Token Verification (2026-08-21)

> **Superseded:** This section records the earlier Moodle Mobile-style token and is retained as investigation history. It does not describe the replacement token intended for `webservicer` / `Weblap szinkron`. See section 22 for the current verification result.

## 21.1. Method and safety boundary

The locally configured `MoodleClient` successfully called only the read-only legacy function `core_webservice_get_site_info`. No other Moodle function was called. No Moodle data or configuration was changed. The Web Service token was neither printed nor written to documentation.

This section's site metadata and function assignments are **verified against the live configured token**. Parameter and behaviour descriptions in sections 19 and 20 remain derived from Moodle source/documentation unless explicitly identified as live-token verified.

## 21.2. Live site and token metadata

The connection succeeded against `https://elearning.imakademia.hu/webservice/rest/server.php`.

`core_webservice_get_site_info` returned:

* site name: `Innováció Menedzsment Akadémia`;
* site URL: `https://elearning.imakademia.hu`;
* Moodle release: `4.5.11 (Build: 20260420)`;
* Moodle version/build identifier: `2024100711`;
* language: `hu`;
* token user ID: `13`;
* token username: an email-style username, not `webservicer`;
* file download enabled: yes;
* file upload enabled: yes;
* functions exposed to this token: `461`.

The returned release confirms the earlier public-build and OpenAPI version identification. The token identity conflicts with the previously provided `webservicer` integration-user description. Moodle administration must confirm whether the locally configured token is the intended dedicated integration token and which External Service it belongs to.

The 461-function inventory resembles a broad Moodle Mobile service surface and includes many activity, messaging and mobile functions unrelated to modules A and B. Only integration-relevant functions are recorded below.

## 21.3. Course functions exposed to the live token

### Available

* `core_course_get_categories`
* `core_course_get_courses`
* `core_course_get_courses_by_field`
* `core_course_search_courses`
* `core_course_get_contents`
* `core_course_get_course_module`
* `core_course_get_course_module_by_instance`
* `core_course_get_enrolled_courses_by_timeline_classification`
* `core_course_get_enrolled_courses_with_action_events_by_timeline_classification`
* `core_course_get_recent_courses`
* `core_course_get_updates_since`

The first four provide relevant category lookup and course retrieval/search. They were not called, so effective record visibility remains untested.

### Standard Moodle functions not assigned to this token/service

* `core_course_create_courses`
* `core_course_update_courses`
* `core_course_delete_courses`
* `core_course_create_categories`
* `core_course_update_categories`
* `core_course_delete_categories`

Standard hide/show uses the `visible` field through `core_course_update_courses`; that function is not assigned.

## 21.4. User functions exposed to the live token

### Available

* `core_user_get_users_by_field`
* `core_user_get_course_user_profiles`

Exact email lookup is therefore exposed through the preferred function. It was not called with any real email, so result visibility and target duplicate-email policy remain untested.

### Standard Moodle functions not assigned to this token/service

* `core_user_get_users`
* `core_user_create_users`
* `core_user_update_users`
* `core_user_delete_users`

Assigned preference/device/profile-view functions do not replace account search/create/update/delete operations.

## 21.5. Enrolment functions exposed to the live token

### Available inspection functions

* `core_enrol_get_course_enrolment_methods`
* `core_enrol_get_enrolled_users`
* `core_enrol_get_users_courses`
* `core_enrol_search_users`

These can inspect enrolment methods, enrolled users and user-course relationships subject to effective capabilities. They were not invoked, so target-course manual-enrolment-instance details remain unresolved.

### Standard Moodle functions not assigned to this token/service

* `enrol_manual_enrol_users`
* `enrol_manual_unenrol_users`
* `core_enrol_edit_user_enrolment`

No assigned function can perform the required manual enrolment, unenrolment or enrolment-state suspension/reactivation. Moodle 4.5 provides these standard operations; their absence is a service-assignment/configuration issue, not evidence that custom development is needed. The preferred standard suspension/reactivation mechanism is the `suspend` value of `enrol_manual_enrol_users`; `core_enrol_edit_user_enrolment` is deprecated.

## 21.6. Modules A and B classification

| Required capability | Function | Classification | Basis |
| --- | --- | --- | --- |
| Create course | `core_course_create_courses` | `STANDARD MOODLE FUNCTION BUT NOT ASSIGNED TO SERVICE` | Standard in Moodle 4.5; absent from live list |
| Update course and hide/show via `visible` | `core_course_update_courses` | `STANDARD MOODLE FUNCTION BUT NOT ASSIGNED TO SERVICE` | Standard in Moodle 4.5; absent live |
| Retrieve exact mapped course | `core_course_get_courses_by_field` | `AVAILABLE` | Present live |
| General course retrieval/search | `core_course_get_courses`, `core_course_search_courses` | `AVAILABLE` | Present live |
| Category lookup | `core_course_get_categories` | `AVAILABLE` | Present live |
| Category management | standard category create/update functions | `STANDARD MOODLE FUNCTION BUT NOT ASSIGNED TO SERVICE` | Absent live; ownership is not required/decided |
| Permanent course deletion | `core_course_delete_courses` | `STANDARD MOODLE FUNCTION BUT NOT ASSIGNED TO SERVICE` | Absent live; normal workflow does not require it |
| Exact user lookup by email | `core_user_get_users_by_field` | `AVAILABLE` | Present live |
| Broad user search | `core_user_get_users` | `STANDARD MOODLE FUNCTION BUT NOT ASSIGNED TO SERVICE` | Absent live; exact lookup may suffice |
| Create user | `core_user_create_users` | `STANDARD MOODLE FUNCTION BUT NOT ASSIGNED TO SERVICE` | Absent live |
| Update mapped user | `core_user_update_users` | `STANDARD MOODLE FUNCTION BUT NOT ASSIGNED TO SERVICE` | Absent live |
| Delete user | `core_user_delete_users` | `STANDARD MOODLE FUNCTION BUT NOT ASSIGNED TO SERVICE` | Absent live; normal cancellation must not use it |
| Inspect course enrolments | `core_enrol_get_enrolled_users` | `AVAILABLE` | Present live |
| Inspect enrolment methods | `core_enrol_get_course_enrolment_methods` | `AVAILABLE` | Present live |
| Enrol/reactivate/suspend user | `enrol_manual_enrol_users` | `STANDARD MOODLE FUNCTION BUT NOT ASSIGNED TO SERVICE` | Standard `suspend` support; absent live |
| Unenrol affected course | `enrol_manual_unenrol_users` | `STANDARD MOODLE FUNCTION BUT NOT ASSIGNED TO SERVICE` | Absent live |
| Structured/queryable teaching sessions | no verified standard course property/function | `UNKNOWN` | Requirements and target structures remain unresolved |

No module A or B operation is currently classified `REQUIRES CUSTOM FUNCTION`. Missing synchronization operations are standard Moodle functions needing deliberate assignment to the intended service plus required role capabilities.

## 21.7. Required service configuration follow-up

For modules A and B, the minimum missing standard write functions to consider assigning are:

```text
core_course_create_courses
core_course_update_courses
core_user_create_users
core_user_update_users
enrol_manual_enrol_users
enrol_manual_unenrol_users
```

`core_course_delete_courses`, category mutation functions and `core_user_delete_users` should not be added merely for completeness because the agreed normal workflow does not require destructive entity deletion. `core_user_get_users` is optional if exact field lookup satisfies the final identity strategy.

No service configuration was changed. Moodle administration must confirm the intended least-privilege External Service and assign only agreed functions and capabilities.

## 21.8. Remaining configuration and business questions

1. Is the configured token for user ID `13` the intended token, or should it belong to `webservicer`?
2. Which External Service produced the returned 461-function set? Site info does not return its name/shortname.
3. Should a dedicated least-privilege custom service replace this broad Mobile-style exposure?
4. Does the intended role have course create/update/visibility, user create/update, manual enrol/unenrol and student-role-assignment capabilities?
5. What Moodle category mapping and student role ID should synchronization use?
6. Do intended courses have enabled manual enrolment instances?
7. Which authentication/password-delivery and username-generation policy should new users use?
8. Does the target allow duplicate emails, and is email approved as the initial matching key?
9. Which target course/user custom fields exist and are editable by the intended integration user?
10. What representation, if any, is required for classification, location, participation mode and structured teaching days?
11. What exact application status triggers enrolment, and should cancellation suspend or unenrol?

---

# 22. Dedicated `Weblap szinkron` Token Verification (2026-08-21)

> **Current result:** After the access-control failures recorded below, a subsequent retry succeeded. The authoritative current identity and function inventory are in section 22.6.

## 22.1. Attempt and result

After the local token was replaced with the token described as belonging to user `webservicer` and service `Weblap szinkron`, the existing `MoodleClient` called only:

```text
core_webservice_get_site_info
```

Moodle returned HTTP 200 containing a Moodle API error:

```text
errorcode: accessexception
exception: webservice_access_exception
message: Hozzáférés-szabályozási kivétel
```

The connection reached the target legacy REST endpoint and Moodle processed the token-bearing request, but the token/service was not permitted to execute `core_webservice_get_site_info`. No alternative Moodle function was called and no data or configuration was changed.

The likely configuration cause is that `core_webservice_get_site_info` is not assigned/accessible through `Weblap szinkron`, or another External Service/token access restriction rejects the call. The error alone does not distinguish those configuration causes. The service configuration must not be modified automatically.

### Retry after a further token update

The local token was updated again and the same single read-only call was repeated at 2026-08-21 17:40 Europe/Budapest time. It produced the same result:

```text
errorcode: accessexception
exception: webservice_access_exception
message: Hozzáférés-szabályozási kivétel
```

The retry therefore did not verify token identity or expose `functions[]`. Sections 22.2–22.5 remain current. This repeated result points to service function assignment, user/service authorization or another token restriction rather than a transient connectivity failure.

### Third token retry

After another local token replacement, the same single read-only call was repeated at 2026-08-21 17:49 Europe/Budapest time. Moodle again returned HTTP 200 with `accessexception` / `webservice_access_exception`. No identity or `functions[]` payload was returned. No other Moodle function was called.

Three dedicated-token attempts have now produced the same access-control result. Further token replacement alone is unlikely to resolve discovery unless `core_webservice_get_site_info` is included in the effective service and the service/user/token restrictions permit it.

## 22.2. Information that could not be verified

Because site info failed before returning its normal payload, the live response did **not** provide:

* token username or user ID;
* Moodle site/version metadata for this token;
* `functions[]` or the number of functions exposed to this token/service;
* confirmation that the token is actually associated with `webservicer`;
* confirmation that the service is actually `Weblap szinkron`.

The Moodle 4.5.11 site/version found with the earlier token remains verified for the target site, but it was not re-returned by this dedicated token.

## 22.3. Required-function verification status

The requested `AVAILABLE` / `MISSING FROM SERVICE` classification cannot be made from the failed response. Absence of a normal `functions[]` result is not evidence that any individual function below is missing.

| Area | Function | Dedicated-token status |
| --- | --- | --- |
| Courses | `core_course_get_courses_by_field` | `UNKNOWN` |
| Courses | `core_course_get_categories` | `UNKNOWN` |
| Courses | `core_course_create_courses` | `UNKNOWN` |
| Courses | `core_course_update_courses` | `UNKNOWN` |
| Users | `core_user_get_users_by_field` | `UNKNOWN` |
| Users | `core_user_create_users` | `UNKNOWN` |
| Users | `core_user_update_users` | `UNKNOWN` |
| Enrolment | `core_enrol_get_course_enrolment_methods` | `UNKNOWN` |
| Enrolment | `core_enrol_get_enrolled_users` | `UNKNOWN` |
| Enrolment | `enrol_manual_enrol_users` | `UNKNOWN` |
| Enrolment | `enrol_manual_unenrol_users` | `UNKNOWN` |
| Diagnostics | `core_webservice_get_site_info` | `MISSING FROM SERVICE` for this token's effective exposed set; the underlying configuration may instead be another service/token access restriction |

The earlier section 21 function list belongs only to the superseded Mobile-style token and must not be used as the `Weblap szinkron` function inventory.

## 22.4. Required configuration follow-up

Moodle administration should inspect `Weblap szinkron` and either:

1. assign `core_webservice_get_site_info` to the service and ensure `webservicer` can invoke it, then repeat this same single read-only verification; or
2. provide an authenticated generated-documentation/service-function export proving the exact assigned function list and token user.

Also verify that the token has no IP, valid-until or other restriction preventing the call and that it is associated with the intended user/service.

No synchronization function should be implemented or treated as available until the dedicated token's exact function set is verified.

## 22.5. Custom-development conclusion

The access-control failure did not indicate a need for custom Moodle development. `core_webservice_get_site_info` and all module A/B candidates are standard Moodle functions. At that point it was a service/token access or function-assignment verification blocker; section 22.6 records its later resolution.

## 22.6. Successful current-token verification

A later retry of the same and only read-only call, `core_webservice_get_site_info`, succeeded. This subsection supersedes the failure status and unknown classifications in sections 22.1–22.5.

### Verified identity and site metadata

* token username: `webservicer`;
* Moodle user ID: `14`;
* site name: `Innováció Menedzsment Akadémia`;
* site URL: `https://elearning.imakademia.hu`;
* Moodle release: `4.5.11 (Build: 20260420)`;
* version/build identifier: `2024100711`;
* language: `hu`;
* exposed function count: `16`.

The site-info payload does not include the External Service name, so `Weblap szinkron` remains the administratively provided service name rather than a value returned by this API call. The user identity and effective function set are live-token verified.

### Exact function list exposed to the current token

```text
core_course_create_courses
core_course_delete_courses
core_course_get_categories
core_course_get_courses
core_course_get_courses_by_field
core_course_update_courses
core_enrol_get_course_enrolment_methods
core_enrol_get_enrolled_users
core_enrol_get_users_courses
core_user_create_users
core_user_get_users
core_user_get_users_by_field
core_user_update_users
core_webservice_get_site_info
enrol_manual_enrol_users
enrol_manual_unenrol_users
```

### Required module A/B classification

| Area | Function | Current service status |
| --- | --- | --- |
| Courses | `core_course_get_courses_by_field` | `AVAILABLE` |
| Courses | `core_course_get_categories` | `AVAILABLE` |
| Courses | `core_course_create_courses` | `AVAILABLE` |
| Courses | `core_course_update_courses` | `AVAILABLE` |
| Courses | `core_course_get_courses` | `AVAILABLE` |
| Courses | `core_course_delete_courses` | `AVAILABLE`, but normal synchronization must not delete courses |
| Users | `core_user_get_users_by_field` | `AVAILABLE` |
| Users | `core_user_get_users` | `AVAILABLE` |
| Users | `core_user_create_users` | `AVAILABLE` |
| Users | `core_user_update_users` | `AVAILABLE` |
| Users | `core_user_delete_users` | `MISSING FROM SERVICE`; not required for normal module B cancellation |
| Enrolment | `core_enrol_get_course_enrolment_methods` | `AVAILABLE` |
| Enrolment | `core_enrol_get_enrolled_users` | `AVAILABLE` |
| Enrolment | `core_enrol_get_users_courses` | `AVAILABLE` |
| Enrolment | `enrol_manual_enrol_users` | `AVAILABLE` |
| Enrolment | `enrol_manual_unenrol_users` | `AVAILABLE` |
| Diagnostics | `core_webservice_get_site_info` | `AVAILABLE` |

`enrol_manual_enrol_users` provides the documented standard `suspend` parameter for enrolment, suspension and reactivation, so a separate enrolment-update function is not required. No write function was executed during verification.

Category lookup is available. Category create/update/delete functions are missing from the service, but modules A/B do not currently assign category-management ownership to imakademia.hu. Course search can use the available `core_course_get_courses_by_field`, `core_course_get_courses` and persisted Moodle IDs; `core_course_search_courses` is not assigned but is not required for the documented exact-mapping flow.

### Current custom-development assessment

The dedicated service exposes all standard functions needed for the currently understood core course, user and manual-enrolment synchronization flows. No custom Moodle function is indicated for those operations.

Structured/queryable teaching-day representation remains unresolved and may use configured course custom fields, another existing Moodle structure or custom Moodle-side development depending on the final Moodle use case.

### Remaining configuration and business questions

1. Confirm the Moodle category mapping and assignable student role ID.
2. Confirm enabled manual enrolment instances on synchronized courses.
3. Confirm effective role capabilities for the assigned write functions; function exposure alone does not prove each operation will pass its capability checks.
4. Decide username generation and authentication/password delivery for new users.
5. Confirm duplicate-email policy and approve email as the initial lookup key.
6. Inspect target course/user custom fields and any mandatory user profile fields.
7. Finalize the application status that triggers enrolment and cancellation suspension versus unenrolment.
8. Define the Moodle-side use case for classification, location, participation mode and teaching-day data.

---

# 23. Final Read-only Target Structure Investigation (2026-08-21)

## 23.1. Safety and scope

Only the already verified read functions were used:

```text
core_course_get_categories
core_course_get_courses_by_field
core_enrol_get_course_enrolment_methods
core_enrol_get_enrolled_users
core_user_get_users_by_field
```

No Moodle data or configuration was changed. Course inspection was limited to three courses in one relevant category and one exact test-course lookup. Participant output was limited to aggregate/structural role and authentication metadata; no participant identity or profile value was recorded.

## 23.2. Verified course categories

| ID | Name | ID number | Parent | Depth/path | Visible | Direct course count |
| --- | --- | --- | --- | --- | --- | --- |
| `1` | `Kategória 1` | empty | root (`0`) | depth 1, `/1` | yes | 7 |
| `5` | `APITESZT` | `322` | `Kategória 1` (`1`) | depth 2, `/1/5` | yes | 0 |
| `2` | `Archív` | empty | root (`0`) | depth 1, `/2` | no | 0 |
| `4` | `5 napos oktatás` | `IMA_5nap` | root (`0`) | depth 1, `/4` | yes | 3 |

`APITESZT` (category ID `5`) is the existing dedicated test category and is the recommended target for the first controlled write test. It is visible, currently empty and nested under category ID `1`. Production category mapping remains a business/configuration question; `5 napos oktatás` (ID `4`) is demonstrably used but should not automatically become the universal integration category.

## 23.3. Observed course-field conventions

Three courses in category ID `4` were inspected:

| Moodle ID | `fullname` | `shortname` | `idnumber` | Dates | `visible` |
| --- | --- | --- | --- | --- | --- |
| `5` | `PRÓBA kurzus_Számviteli alapok` | `Számviteli alapok` | empty | start 2026-05-30; end 2027-05-30 | `1` |
| `6` | `Tűz- és munkavédelem` | `tuzved-2027` | empty | start 2026-05-12; end unset (`0`) | `0` |
| `7` | `Projektmenedzsment` | `pm-2026-stagegate` | empty | start 2026-05-12; end 2027-05-12 | `0` |

Verified observations:

* `fullname` is the descriptive course title.
* `shortname` is required and populated, but current conventions are inconsistent: one resembles the title, while two use compact date/slug-like values. A deterministic integration shortname rule is still required.
* `idnumber` is unused in all three sampled courses, leaving it technically available for an imakademia.hu stable identifier. Its ownership between `local_actual_course_id` and `user_given_id` still requires a decision.
* `categoryid` is used normally; all sampled courses belong to category `4`.
* `startdate` is populated on all sampled courses. `enddate` may be a timestamp or `0` (unset).
* `visible` is actively used for lifecycle/access presentation: one sampled course is visible and two are hidden.

The returned course structures include the standard fields needed for mapper translation (`fullname`, `shortname`, `idnumber`, `categoryid`, `startdate`, `enddate`, `visible`).

## 23.4. Course custom fields

The three category-4 course responses and the exact course-5 response did not contain a `customfields` key. No existing course custom field was exposed for:

* classification;
* place of event;
* way of participation.

This means no usable existing mapping for those values was verified. It does not conclusively prove that the site has no configured course custom fields: fields may be absent because none are configured, none have values, or the integration user's capabilities do not expose them. Moodle administration should inspect course custom-field configuration before the first write payload uses `customfields[]`.

Until then, classification, location and participation mode remain unsupported by a verified target field. They should be omitted from the first controlled write rather than placed into `summary` or another field by assumption.

## 23.5. Manual enrolment and role findings

Course ID `5` (`PRÓBA kurzus_Számviteli alapok`) was used for read-only enrolment inspection.

Verified:

* the course's `enrollmentmethods` contains `manual`, so manual enrolment is enabled for this course;
* `core_enrol_get_enrolled_users` returned one enrolled user;
* that enrolment exposes role shortname `student` with role ID **`5`**.

The verified target student role ID is therefore `5`. The integration should still treat it as target configuration rather than an assumed Moodle default.

`core_enrol_get_course_enrolment_methods` returned an empty array for course ID `5`. The available read surface did not expose a manual enrolment instance ID or instance state. This empty result does not contradict the course structure's explicit `manual` method; it indicates that this endpoint did not return a joinable method for the calling Web Service user. Manual instance ID/state remains unresolved, but the standard `enrol_manual_enrol_users` request does not require an instance ID.

## 23.6. User authentication and onboarding findings

A single enrolled user from the test course was resolved only to inspect non-identifying account metadata. The response:

* did not expose an `auth` value;
* did not expose `confirmed` in this effective response;
* reported `suspended=false`;
* did not contain a `customfields` key.

Consequently, the read-only API did not verify which authentication plugin should be used for newly created learners or whether mandatory user profile custom fields exist.

Moodle 4.5 source documentation still establishes that `core_user_create_users` defaults `auth` to `manual`, permits optional `password`, and supports optional `createpassword=true`. The following target/business choices remain unresolved and must be settled before user creation:

1. whether new learners use `manual` authentication or another configured plugin;
2. deterministic, collision-safe username generation;
3. whether the integration supplies a password or sets `createpassword=true`;
4. whether Moodle is expected to email generated credentials and whether outbound mail/templates are configured for that workflow;
5. reset/first-login expectations and who supports credential delivery failures;
6. whether target user custom fields impose additional mandatory creation values.

## 23.7. Blockers before the first controlled write test

Course creation can be tested independently in empty category `APITESZT` (ID `5`) once these course choices are explicit:

* deterministic `shortname` format;
* which local identifier owns Moodle `idnumber`;
* first-test `fullname`, start/end dates and initial `visible` value;
* confirmation that classification, location, participation mode and teaching days are intentionally omitted until fields/use cases are agreed;
* confirmation that the `webservicer` role has effective create/update capability in category ID `5` (function exposure alone does not prove category-context permission).

Participant creation/enrolment has additional blockers:

* authentication/password/credential-delivery workflow;
* username-generation rule;
* approval of exact email matching and duplicate-email handling;
* confirmation of mandatory user profile fields;
* confirmation that role ID `5` is the intended learner role for integration-created enrolments;
* final application status trigger and cancellation suspend-versus-unenrol rule.

No custom Moodle function is currently indicated for the first controlled course/user/enrolment write test. Structured teaching-day handling remains outside the verified standard-field mapping.

---

# 24. Controlled Live Course Synchronization Smoke Test (2026-08-21)

## 24.1. Scope and retained test data

The first deliberately authorized live write test used a newly created local-only fixture and operated exclusively on the Moodle course created from it. No existing Moodle entity was modified or deleted.

Retained local records:

* local test category ID: `5`, name `Moodle API Smoke Test - 2026-08-21`;
* local `Course` ID: `25`, final name `Moodle API Sync Test Updated`;
* local `ActualCourse` ID: `5`;
* dates: 2026-12-15 through 2026-12-16;
* applications: 0;
* teaching days: 0.

Retained hidden Moodle course:

* Moodle course ID: `12`;
* category ID: `5` (`APITESZT`);
* shortname: `IMA-5`;
* idnumber: `imakademia-actual-course-5`;
* final fullname: `Moodle API Sync Test Updated – 2026-12-15`;
* visible: `0`.

Neither the local fixture nor the Moodle course was automatically removed. The IDs above identify the complete test data for later manual cleanup.

## 24.2. Initial synchronization

`CourseSyncService` first called `core_course_get_courses_by_field` with the deterministic idnumber. Moodle returned zero courses and no warnings. The service then called `core_course_create_courses` with only:

```text
fullname
shortname
idnumber
categoryid
startdate
enddate
visible
```

Moodle created course ID `12`. The local `ActualCourse` stored:

* `moodle_course_id=12`;
* `moodle_sync_status=synced`;
* a populated `moodle_last_synced_at`;
* `moodle_sync_error=null`.

Read-back by exact idnumber returned exactly one course and verified category `5`, visibility `0`, `IMA-5`, the deterministic idnumber, start timestamp 2026-12-15 00:00 UTC and end timestamp 2026-12-16 00:00 UTC.

## 24.3. Update and idempotency

The dedicated local course name was changed to `Moodle API Sync Test Updated`. Synchronization reused persisted Moodle ID `12` and called only `core_course_update_courses`; it did not perform lookup or creation. Read-back verified the changed fullname and unchanged identifiers/category/visibility/dates.

An unchanged third synchronization again used Moodle ID `12`. Final exact-idnumber lookup returned:

* match count: `1`;
* matching Moodle IDs: `[12]`;
* final visibility: `0`.

The live workflow therefore verified creation, persisted-ID updates and idempotency without a duplicate course.

## 24.4. Live response difference

On this target, successful `core_course_update_courses` calls returned a JSON object containing an empty warnings list:

```json
{"warnings": []}
```

This differs from the Moodle 4.5 source-based expectation previously recorded as `null`. `MoodleClient` and `CourseSyncService` correctly accept this successful response because synchronization does not depend on a specific success body. No application change is required.
