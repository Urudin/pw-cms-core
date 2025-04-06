<?php

namespace App\Http\Controllers;

use Anhskohbo\NoCaptcha\NoCaptcha;
use App\Http\Requests\FormSubmitRequest;
use App\Mail\ContactMessage;
use App\Mail\MessageReceived;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController
{
    public function submit(FormSubmitRequest $request) :RedirectResponse
    {
        $validated = $request->validated();

        $customerEmail = $validated['email'];
        $administratorEmails = ['kirtap9408@gmail.com', 'kirtap94-@hotmail.com'];

        Mail::to($customerEmail)
            ->send(new MessageReceived($validated));

        Mail::to($administratorEmails)
            ->send(new ContactMessage($validated));

        // Continue with your form handling logic
        return redirect(route('success-page'));
    }
}
