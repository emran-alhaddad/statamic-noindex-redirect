@php use function Statamic\trans as __; @endphp

@extends('statamic::layout')
@section('title', Statamic::crumb(__('Noindex Redirect'), __('Utilities')))

@section('content')

    <header class="mb-6">
        @include('statamic::partials.breadcrumb', [
            'url' => cp_route('utilities.index'),
            'title' => __('Utilities')
        ])
        <h1>{{ __('Noindex Redirect') }}</h1>
    </header>

    @include('statamic::partials.flash')

    <div class="card">
        <form method="POST" action="{{ cp_route('utilities.noindex-redirect.update') }}">
            @csrf

            <div class="mb-6">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="disable_indexing" value="1" @checked($settings['disable_indexing'])>
                    <span class="font-bold">{{ __('Disable Indexing') }}</span>
                </label>
                <p class="text-sm text-gray mt-1">
                    {{ __('Add X-Robots-Tag: noindex, nofollow to all front-end responses.') }}
                </p>
            </div>

            <div class="mb-6">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="enable_redirect" value="1" @checked($settings['enable_redirect']) data-noindex-redirect-enable>
                    <span class="font-bold">{{ __('Enable Redirect') }}</span>
                </label>
                <p class="text-sm text-gray mt-1">
                    {{ __('Redirect the root path of the CMS subdomain to a specified URL.') }}
                </p>
            </div>

            <div class="mb-6" data-noindex-redirect-url-wrapper @style(['display:none' => ! $settings['enable_redirect']])>
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
                    input.disabled = !show;
                    input.required = show;

                    if (!show) input.value = '';
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
