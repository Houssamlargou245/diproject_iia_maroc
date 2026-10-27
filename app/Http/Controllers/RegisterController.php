<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Locale;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Country;
use App\Services\Totp\TotpService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

/** Account registration, held in an encrypted session until TOTP is confirmed. */
class RegisterController extends Controller
{
    public function create(Request $request): View
    {
        return view('auth.register', [
            'countries' => Country::query()
                ->where('is_active', true)
                ->orderBy('name_'.app()->getLocale())
                ->get(),
            'locale' => Locale::parse(app()->getLocale()),
        ]);
    }

    public function store(RegisterRequest $request, TotpService $totp): RedirectResponse
    {
        $pendingRegistration = [
            'attributes' => $request->accountAttributes(),
            'secret' => $totp->generateSecret(),
            'terms_accepted_at' => now()->toIso8601String(),
        ];

        $request->session()->put(
            'pending_registration',
            Crypt::encryptString(json_encode($pendingRegistration, JSON_THROW_ON_ERROR)),
        );

        return redirect()->route('totp.setup');
    }
}
