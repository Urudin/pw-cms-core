<?php

return [
    'enabled' => (bool) env('MOODLE_ENABLED', false),

    'base_url' => env('MOODLE_BASE_URL'),
    'web_service_token' => env('MOODLE_WEB_SERVICE_TOKEN'),
    'rest_format' => env('MOODLE_REST_FORMAT', 'json'),
    'timeout' => (int) env('MOODLE_TIMEOUT', 30),

    'course_namespace' => env('MOODLE_COURSE_NAMESPACE'),
    'course_category_id' => env('MOODLE_COURSE_CATEGORY_ID'),
    'course_visible' => (bool) env('MOODLE_COURSE_VISIBLE', false),
];
