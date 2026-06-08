@php use function Statamic\trans as __; @endphp

@extends('statamic::layout')
@section('title', Statamic::crumb(__('Noindex Redirect'), __('Utilities')))

@section('content')

    @php
        $disableIndexing = (bool) old('disable_indexing', $settings['disable_indexing']);
        $enableRedirect = (bool) old('enable_redirect', $settings['enable_redirect']);
    @endphp

    <header class="mb-6">
        {{-- Inlined breadcrumb: statamic::partials.breadcrumb was removed in Statamic 6. --}}
        <div class="flex mb-2">
            <a href="{{ cp_route('utilities.index') }}" class="flex items-center text-xs text-gray-700 hover:text-gray-900">
                &larr;&nbsp;<span>{{ __('Utilities') }}</span>
            </a>
        </div>
        <h1>{{ __('Noindex Redirect') }}</h1>
    </header>

    {{-- Inlined flash: statamic::partials.flash was removed in Statamic 6. --}}
    @if (session()->has('success'))
        <div class="alert alert-success mb-6">
            <p>{{ session()->get('success') }}</p>
        </div>
    @endif
    @if (isset($errors) && count($errors) > 0)
        <div class="alert alert-danger mb-6">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="card">
        <form method="POST" action="{{ cp_route('utilities.noindex-redirect.update') }}">
            @csrf

            <div class="mb-6">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="disable_indexing" value="1" @checked($disableIndexing)>
                    <span class="font-bold">{{ __('Disable Indexing') }}</span>
                </label>
                <p class="text-sm text-gray mt-1">
                    {{ __('Add X-Robots-Tag: noindex, nofollow to all front-end responses.') }}
                </p>
            </div>

            <div class="mb-6">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="enable_redirect" value="1" @checked($enableRedirect) data-noindex-redirect-enable>
                    <span class="font-bold">{{ __('Enable Redirect') }}</span>
                </label>
                <p class="text-sm text-gray mt-1">
                    {{ __('Redirect all front-end requests to a specified URL.') }}
                </p>
            </div>

            {{-- Visible by default so it works on Statamic 6, where the utility HTML is
                 injected via Inertia/v-html and the toggle <script> below does not run.
                 On Statamic 4/5 the script hides it when "Enable Redirect" is off. --}}
            <div class="mb-6" data-noindex-redirect-url-wrapper>
                <label class="block font-bold mb-1" for="redirect_url">{{ __('Redirect URL') }}</label>
                <input
                    class="input-text w-full"
                    type="url"
                    id="redirect_url"
                    name="redirect_url"
                    data-noindex-redirect-url-input
                    value="{{ old('redirect_url', $settings['redirect_url'] ?? '') }}"
                    placeholder="https://example.com"
                />
                <p class="text-sm text-gray mt-1">
                    {{ __('Absolute URL to redirect to (e.g., https://example.com).') }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="btn-primary">{{ __('Save') }}</button>
                @if ($has_stored_settings)
                    <button
                        type="submit"
                        class="btn"
                        formaction="{{ cp_route('utilities.noindex-redirect.reset') }}"
                        formnovalidate
                    >{{ __('Reset to config') }}</button>
                @endif
            </div>
        </form>
    </div>

    <p class="text-xs text-gray mt-3">
        {{ __('Stored overrides file: :path', ['path' => $storage_relative_path]) }}
        @if ($has_stored_settings)
            ({{ __('active') }})
        @else
            ({{ __('not set') }})
        @endif
    </p>

    <script>
        (function () {
            const init = () => {
                const enable = document.querySelector('[data-noindex-redirect-enable]');
                const wrapper = document.querySelector('[data-noindex-redirect-url-wrapper]');
                const input = document.querySelector('[data-noindex-redirect-url-input]');

                if (!enable || !wrapper || !input) return;

                const toggle = () => {
                    const show = enable.checked;
                    wrapper.style.display = show ? '' : 'none';
                    input.required = show;
                };

                enable.addEventListener('change', toggle);
                toggle();
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
            } else {
                init();
            }
        })();
    </script>

@endsection
