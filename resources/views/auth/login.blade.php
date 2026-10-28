@extends('layouts.app')

@section('title', __('login.title'))

@section('content')
    {{-- A standalone form screen: no page hero, so the card and the shell carry
         the layout. The classes are components rather than utilities, so the
         sign-in, registration and verification screens stay identical in shape
         and one edit changes all three. --}}
    <div class="section-padding-04">
        <div class="container">
            <div class="app-shell app-shell--narrow">

                <h1 class="app-title">@lang('login.title')</h1>

                <div class="app-card">
                    <form method="POST" action="{{ route('login.store') }}">
                        @csrf

                        <x-form.field name="identifier" :label="__('login.identifier')" required>
                            {{-- dir="ltr": the value may be an email address or a
                                 phone number, and neither should be
                                 bidi-reordered on an Arabic page. --}}
                            <input type="text" name="identifier" id="identifier" required
                                   autocomplete="username"
                                   dir="ltr"
                                   value="{{ old('identifier') }}"
                                   @class(['input', 'input-error' => $errors->has('identifier')])
                                   @if ($errors->has('identifier')) aria-invalid="true" aria-describedby="identifier-error" @endif>
                        </x-form.field>

                        <x-form.field name="password" :label="__('register.password')" required>
                            <div class="pw-field">
                                <input type="password" name="password" id="password" required
                                       autocomplete="current-password"
                                       dir="ltr"
                                       @class(['input', 'input-error' => $errors->has('password')])
                                       @if ($errors->has('password')) aria-invalid="true" aria-describedby="password-error" @endif>
                                <button type="button" class="pw-toggle" data-pw-toggle="password"
                                        data-label-show="{{ __('misc.show_password') }}"
                                        data-label-hide="{{ __('misc.hide_password') }}"
                                        aria-label="{{ __('misc.show_password') }}" aria-pressed="false">
                                    <i class="far fa-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                        </x-form.field>

                        <div class="app-check mb-4">
                            <input type="checkbox" name="remember" id="remember" value="1" @checked(old('remember'))>
                            <label for="remember">@lang('login.remember')</label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">@lang('login.submit')</button>
                    </form>
                </div>

                <p class="app-note mt-4 text-center">@lang('login.member_notice')</p>

                <p class="app-note mt-4 text-center">
                    <span class="text-dark">@lang('login.no_account')</span>
                    <a href="{{ route('register') }}" class="app-link">@lang('login.register_link')</a>
                </p>
            </div>
        </div>
    </div>
@endsection
