<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SponsorshipRequest;
use App\Mail\SponsorshipRequestMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

class SponsorshipRequestController extends Controller
{
    public function store(SponsorshipRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['consent', 'website']);

        Mail::to(config('mail.sponsoring_to', config('mail.from.address')))
            ->send(new SponsorshipRequestMail($data));

        return redirect()->to(url()->previous().'#sponsor-form')
            ->with('sponsor_sent', true);
    }
}
