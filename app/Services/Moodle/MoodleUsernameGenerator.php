<?php

namespace App\Services\Moodle;

use Illuminate\Support\Str;

class MoodleUsernameGenerator
{
    public function generate(string $email): string
    {
        $username = Str::lower(trim($email));

        if (filter_var($username, FILTER_VALIDATE_EMAIL) === false) {
            throw new MoodleException('A valid participant email address is required for the Moodle username.');
        }

        return $username;
    }
}
