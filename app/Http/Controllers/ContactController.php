<?php

namespace App\Http\Controllers;

use App\Http\Requests\FormSubmitRequest;
use App\Mail\ContactMessage;
use App\Mail\MessageReceived;
use App\Models\UserSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

class ContactController
{
    public function submit(FormSubmitRequest $request) :RedirectResponse
    {
        $validated = $request->validated();

        $customerEmail = $validated['email'];
        $administratorEmails = UserSetting::getAdminEmailAddresses();

        Mail::to($customerEmail)
            ->send(new MessageReceived($validated));

        Mail::to($administratorEmails)
            ->send(new ContactMessage($validated));

        // Continue with your form handling logic
        return redirect(route('pages.show', ['slug' => 'koszonjuk-erdeklodeset']));
    }
}
