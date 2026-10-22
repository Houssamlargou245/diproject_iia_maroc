<?php

namespace App\Providers;

use App\Contracts\OtpChannel;
use App\Enums\OtpDriver;
use App\Hashing\LegacyBcryptHasher;
use App\Models\Edition;
use App\Services\Otp\LogOtpChannel;
use App\Services\Otp\SmsOtpChannel;
use App\Services\Otp\TelegramOtpChannel;
use App\Services\Payment\CmiGateway;
use App\Services\Payment\PaymentGateway;
use App\Services\Payment\TestGateway;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator as ValidatorInstance;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->trustConfiguredProxies();

        // One binding for every delivery path, chosen by config, so nothing in
        // the application has to know which channel is active.
        $this->app->singleton(OtpChannel::class, function (): OtpChannel {
            return match (config('otp.driver', OtpDriver::Log->value)) {
                OtpDriver::Sms->value => $this->app->make(SmsOtpChannel::class),
                OtpDriver::Telegram->value => $this->app->make(TelegramOtpChannel::class),
                default => $this->app->make(LogOtpChannel::class),
            };
        });

        // Likewise for the payment gateway. The checkout depends on the
        // contract, never on a concrete driver, so PAYMENT_DRIVER=test
        // rehearses the whole flow offline and the controller code is
        // identical in both cases.
        $this->app->singleton(PaymentGateway::class, function (): PaymentGateway {
            return match (config('cmi.driver')) {
                'cmi' => $this->app->make(CmiGateway::class),
                default => $this->app->make(TestGateway::class),
            };
        });
    }

    /**
     * Trust the reverse proxy, but only if one is declared.
     *
     * APP_TRUSTED_PROXIES takes a comma-separated list of IPs or CIDR ranges,
     * e.g. "127.0.0.1,10.0.0.0/8". Headers are listed explicitly rather than
     * using Request::HEADER_X_FORWARDED_ALL, so a client cannot inject
     * X-Forwarded-Host and rewrite generated URLs, or a forwarded port and break
     * the scheme.
     *
     * Leaving this unset is the safe default: no forwarded header is honoured,
     * so Request::ip() is the real peer address. That matters because the OTP
     * and login rate limits key on it — trusting every proxy would let a caller
     * present a fresh IP on each request and defeat the limits.
     */
    private function trustConfiguredProxies(): void
    {
        $trusted = config('app.trusted_proxies');

        if (blank($trusted)) {
            return;
        }

        Request::setTrustedProxies(
            array_values(array_filter(array_map('trim', explode(',', (string) $trusted)))),
            Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_AWS_ELB,
        );
    }

    public function boot(): void
    {
        // Fail loudly in production on a configuration mistake that would only
        // otherwise surface as a confusing 500 on the registration form.
        if ($this->app->isProduction()) {
            $this->assertProductionConfiguration();
        }

        // Bootstrap's pager, not the framework's default Tailwind markup. Both
        // layouts load `bootstrap.min.css` and no Tailwind build, so the default
        // pager rendered a wall of unstyled Tailwind classes that looked broken
        // on the orders list and every admin table.
        Paginator::useBootstrapFive();

        $this->configureUrlGeneration();
        $this->configurePasswordRules();
        $this->registerHashing();
        $this->registerValidationRules();
        $this->shareConferenceContext();
        $this->preventLazyModelBugs();
    }

    /**
     * Make the current edition available to every view.
     *
     * The layout needs the year, the venue and the contact details on every
     * page, and passing them from each controller is how the 2024 build ended up
     * with three different venue strings. `Edition::current()` memoises the
     * query for the request, so this costs one query per request, not one per
     * view.
     */
    private function shareConferenceContext(): void
    {
        View::composer('*', function (ViewContract $view): void {
            if (! array_key_exists('currentEdition', $view->getData())) {
                $view->with('currentEdition', Edition::current());
            }
        });
    }

    /**
     * Custom validation rules, registered by name.
     *
     * `normalised_phone` is a named rule rather than a Rule object so the
     * validation message key in the form request is stable and translatable,
     * and so the check has one implementation shared by registration, profile
     * edits and the admin panel.
     *
     * It runs on the value the form request has already canonicalised, so all it
     * has to confirm is the shape: `+` followed by 8–15 digits, per E.164.
     */
    private function registerValidationRules(): void
    {
        // Note the alias: the callbacks are handed the concrete
        // Illuminate\Validation\Validator instance, not the facade, so type-hinting
        // the facade here would throw a TypeError the first time the rule runs.
        Validator::extend('normalised_phone', function (string $attribute, mixed $value, array $parameters, ValidatorInstance $validator): bool {
            if (! is_string($value) || $value === '') {
                return false;
            }

            return (bool) preg_match('/^\+[1-9]\d{7,14}$/', $value);
        });
    }

    /**
     * Force HTTPS and stable root URLs.
     *
     * Without this, Laravel derives the scheme from the incoming request, so a
     * proxied site generates `http://` links and session cookies that browsers
     * will refuse to send back over TLS.
     */
    private function configureUrlGeneration(): void
    {
        if (config('app.force_https')) {
            URL::forceScheme('https');
        }
    }

    /**
     * `legacy` is the default hash driver (config/hashing.php). It is a bcrypt
     * hasher that additionally verifies the phpass `$P$` hashes carried over
     * from the 2024 WordPress import, which `password_verify()` cannot read.
     * Registered under its own name so the built-in bcrypt driver stays
     * untouched and can still be selected explicitly via HASH_DRIVER.
     */
    private function registerHashing(): void
    {
        Hash::extend('legacy', fn (): LegacyBcryptHasher => new LegacyBcryptHasher(
            (array) config('hashing.bcrypt'),
        ));
    }

    /**
     * Password strength, in one place.
     *
     * `min:12` is stricter than the 2024 build, which accepted anything above
     * six characters. Compromised-password checking uses the Have I Been Pwned
     * k-anonymity range API: only a five-character hash prefix ever leaves the
     * server.
     */
    private function configurePasswordRules(): void
    {
        Password::defaults(fn () => Password::min(12)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->symbols()
            ->uncompromised());
    }

    /**
     * Fail on an N+1 in development instead of discovering it in production.
     */
    private function preventLazyModelBugs(): void
    {
        $strict = $this->app->environment('local', 'testing');

        // Each of these is a static void method, so they are called
        // individually rather than chained.
        Model::preventLazyLoading($strict);
        Model::preventSilentlyDiscardingAttributes($strict);
        Model::preventAccessingMissingAttributes($strict);
    }

    /**
     * @throws RuntimeException
     */
    private function assertProductionConfiguration(): void
    {
        $problems = [];

        if (config('otp.driver') === OtpDriver::Log->value) {
            $problems[] = 'OTP_DRIVER=log is a development-only driver; it would write every code to the log file.';
        }

        if (blank(config('app.key'))) {
            $problems[] = 'APP_KEY is empty; sessions and encrypted values are unreadable.';
        }

        if (config('app.debug')) {
            $problems[] = 'APP_DEBUG=true exposes stack traces and environment values to visitors.';
        }

        if (config('session.secure') !== true) {
            $problems[] = 'SESSION_SECURE is not true; session cookies would travel over plain HTTP.';
        }

        if ($problems !== []) {
            throw new RuntimeException(
                "Refusing to boot in production:\n - ".implode("\n - ", $problems)
            );
        }
    }
}
