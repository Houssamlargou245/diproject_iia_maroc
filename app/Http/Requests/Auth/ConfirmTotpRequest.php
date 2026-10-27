<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The code typed from the authenticator app.
 *
 * `digits` with a fixed length rather than a regex: the value arrives from a
 * six-box-ish numeric input, and rejecting anything that is not exactly the
 * right number of digits before it reaches the library keeps a paste of a whole
 * otpauth URI from becoming an exception instead of a field error.
 *
 * Separators are *not* stripped here. The service normalises before checking,
 * because an authenticator app displays codes in groups and people retype them
 * with the spacing; the rule only rejects what could not be a code at all.
 */
class ConfirmTotpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null || $this->session()->has('pending_registration');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $digits = (int) config('totp.digits', 6);

        return [
            'code' => ['required', 'string', 'digits:'.$digits, 'size:'.$digits],
        ];
    }

    public function messages(): array
    {
        return [
            'code.digits' => __('totp.invalid_code'),
            'code.size' => __('totp.invalid_code'),
            'code.required' => __('totp.code_required'),
        ];
    }

    public function attributes(): array
    {
        return ['code' => __('totp.code_label')];
    }

    public function code(): string
    {
        return (string) $this->input('code');
    }
}
