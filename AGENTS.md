# Project Instructions

## General

Before modifying the codebase:

1. Inspect the existing implementation and architecture relevant to the task.
2. Prefer existing project conventions, patterns and abstractions over introducing new ones.
3. Keep changes focused on the requested task.
4. Do not refactor unrelated code unless it is necessary for the implementation.
5. Do not add new dependencies unless clearly justified.
6. Do not commit changes unless explicitly requested.

When requirements are unclear, investigate first instead of making assumptions.

---

## Moodle Integration

For any task related to the Moodle integration, read the documentation under:

```text
docs/moodle-integration/
```

Start with:

```text
docs/moodle-integration/README.md
```

Then read:

```text
docs/moodle-integration/functional-spec.md
docs/moodle-integration/moodle-api-notes.md
docs/moodle-integration/decisions.md
```

### Documentation responsibilities

Treat `functional-spec.md` as the source of truth for agreed business behaviour.

Treat `moodle-api-notes.md` as technical integration documentation.

Some API functions listed there may initially be candidates rather than verified functions. Do not assume that a Moodle API function is available on the target installation until it has been verified against the target Moodle Web Service documentation.

Treat `decisions.md` as the record of architectural and business decisions already made.

Do not silently contradict an existing documented decision.

If a new architectural or business decision is made during implementation, update `decisions.md`.

If technical information about the target Moodle installation is discovered or verified, update `moodle-api-notes.md`.

---

## Moodle Integration Principles

The following rules are important:

* `imakademia.hu` is the primary source of course and application data.
* Moodle synchronization must be safe to retry.
* Synchronization must avoid duplicate courses, users and enrolments.
* Persisted Moodle entity IDs should be reused after successful mapping.
* A temporary Moodle failure must not prevent normal administration in `imakademia.hu`.
* Cancelling one course application must not delete the Moodle user account.
* Operations for one course must not affect unrelated Moodle course enrolments.
* Only data with a defined Moodle use case should be transferred.
* Secrets, credentials and Web Service tokens must never be committed to the repository.
* Do not invent missing business rules.

If the specification does not define required behaviour, report the ambiguity and ask for clarification before implementing consequential behaviour.

---

## Investigation Before Implementation

For non-trivial tasks, investigate the existing code before writing the implementation.

Identify:

* relevant models;
* database relationships;
* existing services and repositories;
* application status transitions;
* existing jobs, queues or event mechanisms;
* existing error-handling and logging conventions;
* existing test patterns.

Prefer extending the current architecture over creating a parallel architecture specifically for Moodle.

When asked to investigate or plan, do not modify production code unless explicitly requested.

---

## Testing

New integration behaviour should be covered by automated tests where practical.

Before finishing an implementation task:

1. run the most relevant targeted tests;
2. run additional related tests where appropriate;
3. report exactly which tests were executed;
4. report any failures, including failures believed to be pre-existing.

Do not claim that tests pass unless they were actually executed.

Do not weaken or remove existing tests merely to make a new implementation pass.

---

## Error Handling

External Moodle communication is an integration boundary and should be treated accordingly.

Handle expected remote failures explicitly.

Where required by the functional specification:

* record failed synchronization;
* make failed operations retryable;
* preserve the local imakademia.hu operation;
* provide enough diagnostic information to investigate failures.

Avoid swallowing exceptions without logging or otherwise recording the failure.

---

## Scope Discipline

Implement only the currently requested integration module or behaviour.

In particular, do not implement Moodle → imakademia.hu event handling unless the requested task explicitly concerns that module.

Do not implement speculative Moodle plugins, custom Web Service functions or custom Moodle fields before verifying that the standard API cannot satisfy the requirement.

When a task reveals work outside the current scope, report it separately instead of silently expanding the implementation.
