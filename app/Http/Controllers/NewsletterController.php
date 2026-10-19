<?php

namespace App\Http\Controllers;

use App\Mail\NewsletterConfirmation;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class NewsletterController extends Controller
{
    /** Don't re-send the confirmation e-mail more often than this. */
    private const RESEND_AFTER_MINUTES = 5;

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        // Honeypot: bots fill "company". Pretend it worked, store nothing.
        if ($request->filled('company')) {
            return $this->success($request, __('footer.newsletter_success'));
        }

        $validator = Validator::make(
            ['email' => Str::lower(trim((string) $request->input('email')))],
            ['email' => ['required', 'email:rfc', 'max:190']],
            [
                'email.required' => __('footer.newsletter_err_required'),
                'email.email' => __('footer.newsletter_err_invalid'),
                'email.max' => __('footer.newsletter_err_invalid'),
            ]
        );

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'errors' => $validator->errors()], 422);
            }

            return redirect()->to($this->backUrl())->withErrors($validator, 'newsletter');
        }

        $locale = app()->getLocale();
        $subscriber = NewsletterSubscriber::firstOrNew([
            'email' => $validator->validated()['email'],
        ]);

        // Already confirmed: say so, send nothing.
        if ($subscriber->exists && $subscriber->isSubscribed()) {
            return $this->success($request, __('footer.newsletter_already'));
        }

        // Pending and a mail just went out: don't spam the inbox.
        $justSent = $subscriber->exists
            && $subscriber->status === NewsletterSubscriber::STATUS_PENDING
            && $subscriber->updated_at?->gt(now()->subMinutes(self::RESEND_AFTER_MINUTES));

        if (! $justSent) {
            // New, pending (resend) or previously unsubscribed (re-opt-in).
            // Consent evidence is refreshed every time the person opts in again.
            $subscriber->fill([
                'locale' => $locale,
                'status' => NewsletterSubscriber::STATUS_PENDING,
                'consent_locale' => $locale,
                'consent_ip' => $request->ip(),
                'unsubscribed_at' => null,
            ])->save();

            try {
                Mail::to($subscriber->email)->send(new NewsletterConfirmation($subscriber));
            } catch (\Throwable $e) {
                report($e);

                return $this->failure($request, __('footer.newsletter_err_generic'));
            }
        }

        return $this->success($request, __('footer.newsletter_success'));
    }

    /** Target of the signed link in the confirmation e-mail. */
    public function confirm(NewsletterSubscriber $subscriber): RedirectResponse
    {
        if (! $subscriber->isSubscribed()) {
            $subscriber->confirm();
        }

        return redirect()
            ->to(route('home').'#newsletter')
            ->with('newsletter_status', __('footer.newsletter_confirmed'));
    }

    private function success(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => $message]);
        }

        return redirect()->to($this->backUrl())->with('newsletter_status', $message);
    }

    private function failure(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['ok' => false, 'message' => $message], 500);
        }

        return redirect()->to($this->backUrl())->withErrors(['email' => $message], 'newsletter');
    }

    /** Previous page, scrolled back to the newsletter card. */
    private function backUrl(): string
    {
        return strtok(url()->previous(), '#').'#newsletter';
    }
}
