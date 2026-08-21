# Moodle Integration

This directory contains the documentation for the integration between **imakademia.hu** and Moodle.

The purpose of these documents is to provide a persistent source of context for development and AI-assisted implementation.

Before working on any Moodle integration task, read this file and the relevant documents in this directory.

## Goal

The integration connects the existing imakademia.hu administration system with Moodle through Moodle Web Services.

The main goals are:

1. Synchronize courses from imakademia.hu to Moodle.
2. Synchronize participants and course enrolments to Moodle.
3. Handle relevant events or state changes originating from Moodle where required.
4. Ensure Moodle communication failures do not prevent normal use of the imakademia.hu administration system.

The integration should use Moodle's existing Web Service / External API functionality wherever possible.

Custom Moodle-side functionality should only be introduced when the required behaviour cannot reasonably be implemented using the available APIs.

## Integration Direction

The primary direction of data flow is:

```text
imakademia.hu
      |
      | courses
      | users
      | enrolments
      v
    Moodle
```

Most business data originates from imakademia.hu.

A secondary Moodle → imakademia.hu communication channel may be implemented for specific events once those requirements are finalized.

## Scope

The integration is divided into three logical modules.

### A. Course synchronization

Responsible for:

* creating Moodle courses;
* updating already synchronized courses;
* mapping an imakademia.hu course instance to exactly one Moodle course;
* handling relevant course lifecycle changes;
* preventing duplicate course creation;
* handling synchronization failures.

### B. Participant synchronization

Responsible for:

* finding or creating Moodle users;
* assigning participants to the appropriate Moodle course;
* updating relevant participant data;
* handling cancellation of an application;
* suspending or removing access to the affected course without deleting the Moodle account;
* preventing duplicate users and enrolments where possible;
* handling synchronization failures.

### C. Moodle-originated events

Optional functionality for events that originate in Moodle and must be communicated back to imakademia.hu.

Possible examples include:

* Moodle-side user or enrolment status changes;
* course completion or course state changes;
* minimum participant-related feedback if this is ultimately implemented on the Moodle side;
* other Moodle-side events relevant to the administration system.

The exact scope of this module is not yet finalized.

Errors returned directly from Moodle API calls initiated by modules A or B are part of those modules and are not considered Moodle-originated events.

## Core Integration Principles

### imakademia.hu remains operational independently

A temporary Moodle outage or failed synchronization must not prevent normal administration in imakademia.hu.

Failed synchronization should be detectable and retryable.

### Synchronization must be idempotent

Repeating the same operation must not accidentally create:

* duplicate Moodle courses;
* duplicate Moodle users;
* duplicate course enrolments.

### Existing records should be reused

Where possible:

* an existing Moodle course linked to an imakademia.hu course should be updated instead of recreated;
* an existing Moodle user should be reused instead of creating another account.

The initial proposed user matching mechanism is the participant's email address.

### Moodle accounts must not be deleted as part of normal course lifecycle operations

Cancelling a course application or removing access to a course must only affect the relevant enrolment.

A Moodle user may participate in multiple courses.

### Do not invent business rules

If required behaviour is not defined in the project documentation, do not silently make assumptions.

Record unresolved questions and clarify them before implementing behaviour that could affect business data.

## Documentation

The documentation in this directory is intended to be split into the following files:

```text
docs/moodle-integration/
├── README.md
├── functional-spec.md
├── moodle-api-notes.md
├── decisions.md
└── progress.md
```

### `functional-spec.md`

Detailed business and synchronization requirements.

This is the primary source of truth for expected integration behaviour.

### `moodle-api-notes.md`

Technical notes about the actual Moodle installation, including:

* available Web Service functions;
* authentication;
* request and response formats;
* required permissions;
* Moodle fields;
* custom fields;
* limitations discovered during implementation.

### `decisions.md`

Important architectural and business decisions made during implementation, including the reasoning behind them.

### `progress.md`

Current implementation state, unresolved questions and known issues.

## External Moodle Documentation

General Moodle documentation provided for this project:

* External Services API:
  https://moodledev.io/docs/5.3/apis/subsystems/external

* Creating a Web Service client:
  https://docs.moodle.org/dev/Creating_a_web_service_client

* Using Web Services:
  https://docs.moodle.org/en/Using_web_services

The target Moodle installation also exposes its own Web Service documentation page.

Access to the target Moodle environment and its Web Service documentation must be obtained before implementation details depending on the actual enabled API functions are finalized.

## Current Status

The integration requirements and initial development estimate have been accepted.

The functional behaviour for course and participant synchronization has been outlined.

Moodle-specific technical access has not yet been provided.

Implementation should therefore begin with investigation of the existing imakademia.hu architecture and preparation of the integration boundaries, while avoiding assumptions about Moodle functions that have not yet been verified against the target installation.
