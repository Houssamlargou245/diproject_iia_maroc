<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SponsorshipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company' => ['required', 'string', 'max:150'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:150'],
            'phone' => ['required', 'string', 'max:40'],
            'formula' => ['required', 'in:platinum,gold,silver,lab'],
            'message' => ['nullable', 'string', 'max:2000'],
            'consent' => ['accepted'],
            'website' => ['prohibited'], // honeypot: left empty by humans
        ];
    }
}
