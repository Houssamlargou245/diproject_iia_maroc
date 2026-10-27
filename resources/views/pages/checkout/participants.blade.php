{{--
    One entry per place, server-rendered.
--}}
@php
    $participantValues = $participantValues ?? [];
@endphp

@for ($i = 0; $i < $quote->participantCount; $i++)
    @php $isMemberSlot = $i < $quote->memberCount; @endphp

    <fieldset class="border rounded p-3 mb-3">
        <legend class="float-none w-auto px-2 fs-6">
            {{ __('order.participant_number', ['number' => $i + 1]) }}
            @if ($isMemberSlot)
                <span class="badge bg-success">@lang('pricing.member')</span>
            @endif
        </legend>

        <div class="row">
            <div class="col-md-12">
                <x-form.field :name="'participants.'.$i.'.full_name'"
                              :label="__('order.participant_name')" required>
                    {{-- REMOVED 'required' FROM INPUT BELOW --}}
                    <input type="text" dir="auto"
                           name="participants[{{ $i }}][full_name]"
                           id="participants-{{ $i }}-full_name"
                           value="{{ data_get($participantValues, $i.'.full_name') }}"
                           class="form-control @error('participants.'.$i.'.full_name') is-invalid @enderror">
                    
                    {{-- MANUAL ERROR BLOCK DELETED FROM HERE --}}
                </x-form.field>
            </div>

            <div class="col-md-6">
                <x-form.field :name="'participants.'.$i.'.job_title'"
                              :label="__('register.job_title')">
                    <input type="text" dir="auto"
                           name="participants[{{ $i }}][job_title]"
                           id="participants-{{ $i }}-job_title"
                           value="{{ data_get($participantValues, $i.'.job_title') }}"
                           class="form-control @error('participants.'.$i.'.job_title') is-invalid @enderror">
                </x-form.field>
            </div>

            <div class="col-md-6">
                <x-form.field :name="'participants.'.$i.'.email'"
                              :label="__('register.email')">
                    <input type="email" dir="ltr"
                           name="participants[{{ $i }}][email]"
                           id="participants-{{ $i }}-email"
                           value="{{ data_get($participantValues, $i.'.email') }}"
                           class="form-control @error('participants.'.$i.'.email') is-invalid @enderror">
                </x-form.field>
            </div>

            <div class="col-md-6">
                <x-form.field :name="'participants.'.$i.'.phone'"
                              :label="__('register.phone')">
                    <input type="tel" dir="ltr"
                           name="participants[{{ $i }}][phone]"
                           id="participants-{{ $i }}-phone"
                           value="{{ data_get($participantValues, $i.'.phone') }}"
                           class="form-control @error('participants.'.$i.'.phone') is-invalid @enderror">
                </x-form.field>
            </div>
        </div>
    </fieldset>
@endfor