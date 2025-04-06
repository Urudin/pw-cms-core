<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FormSubmitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'g-recaptcha-response' => 'required|captcha',
            'name' => 'required|string',
            'company' => 'nullable|string',
            'phone' => 'required|string',
            'email' => 'required|string',
            'message' => 'required|string',
            'consent' => 'required',
        ];
    }

    public function messages(): array
    {

        return [
            'custom' => [
                'g-recaptcha-response' => [
                    'required' => 'Kérem erősítse meg, hogy nem robot.',
                    'captcha' => 'Captcha hiba! próbálja meg később vagy vegye fel velünk a kapcsolatot.',
                ],
            ],
        ];
    }
}
