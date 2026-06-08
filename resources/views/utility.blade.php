@php use function Statamic\trans as __; @endphp

@extends('statamic::layout')
@section('title', Statamic::crumb(__('Noindex Redirect'), __('Utilities')))

{{--
    This view is intentionally self-contained (inline styles, no <script>):

    On Statamic 6 the Control Panel renders a utility view by compiling its HTML
    as a Vue component template (see DynamicHtmlRenderer.vue). That means:
      * <script> tags are stripped by the Vue template compiler, so any JS here
        would silently not run (and could break compilation).
      * The legacy CP CSS classes (card, btn-primary, input-text, ...) were
        removed/renamed in the v6 redesign, so relying on them yields an
        unstyled page.
    Using inline styles and no JS keeps the page styled and fully functional on
    Statamic 4, 5 and 6 without any version-specific code.
--}}

@section('content')

    <div style="max-width: 42rem;">

        <header style="margin-bottom: 1.5rem;">
            {{-- Inlined breadcrumb (statamic::partials.breadcrumb was removed in Statamic 6). --}}
            <a href="{{ cp_route('utilities.index') }}" style="display:inline-block;font-size:.8rem;opacity:.7;text-decoration:none;margin-bottom:.5rem;">
                &larr; {{ __('Utilities') }}
            </a>
            <h1 style="font-size:1.5rem;font-weight:700;margin:0;">{{ __('Noindex Redirect') }}</h1>
        </header>

        {{-- Inlined flash (statamic::partials.flash was removed in Statamic 6). --}}
        @if (session()->has('success'))
            <div style="border-left:4px solid #16a34a;background:rgba(22,163,74,.12);padding:.75rem 1rem;border-radius:.375rem;margin-bottom:1rem;">
                {{ session()->get('success') }}
            </div>
        @endif
        @if (isset($errors) && count($errors) > 0)
            <div style="border-left:4px solid #dc2626;background:rgba(220,38,38,.12);padding:.75rem 1rem;border-radius:.375rem;margin-bottom:1rem;">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div style="border:1px solid rgba(127,127,127,.25);border-radius:.5rem;padding:1.5rem;">
            <form method="POST" action="{{ cp_route('utilities.noindex-redirect.update') }}">
                @csrf

                <div style="margin-bottom:1.5rem;">
                    <label style="display:flex;align-items:center;gap:.5rem;font-weight:700;cursor:pointer;">
                        <input type="checkbox" name="disable_indexing" value="1" @checked((bool) old('disable_indexing', $settings['disable_indexing']))>
                        <span>{{ __('Disable Indexing') }}</span>
                    </label>
                    <p style="font-size:.85rem;opacity:.7;margin:.35rem 0 0;">
                        {{ __('Add X-Robots-Tag: noindex, nofollow to all front-end responses.') }}
                    </p>
                </div>

                <div style="margin-bottom:1.5rem;">
                    <label style="display:flex;align-items:center;gap:.5rem;font-weight:700;cursor:pointer;">
                        <input type="checkbox" name="enable_redirect" value="1" @checked((bool) old('enable_redirect', $settings['enable_redirect']))>
                        <span>{{ __('Enable Redirect') }}</span>
                    </label>
                    <p style="font-size:.85rem;opacity:.7;margin:.35rem 0 0;">
                        {{ __('Redirect all front-end GET/HEAD requests to the URL below (only applied when this is enabled).') }}
                    </p>
                </div>

                <div style="margin-bottom:1.5rem;">
                    <label for="redirect_url" style="display:block;font-weight:700;margin-bottom:.35rem;">{{ __('Redirect URL') }}</label>
                    <input
                        type="url"
                        id="redirect_url"
                        name="redirect_url"
                        value="{{ old('redirect_url', $settings['redirect_url'] ?? '') }}"
                        placeholder="https://example.com"
                        style="display:block;width:100%;padding:.5rem .75rem;border:1px solid rgba(127,127,127,.4);border-radius:.375rem;background:#fff;color:#1a1a1a;"
                    >
                    <p style="font-size:.85rem;opacity:.7;margin:.35rem 0 0;">
                        {{ __('Absolute URL to redirect to (e.g., https://example.com).') }}
                    </p>
                </div>

                <div style="display:flex;align-items:center;gap:.5rem;">
                    <button type="submit" style="padding:.5rem 1rem;border:0;border-radius:.375rem;background:#2563eb;color:#fff;font-weight:600;cursor:pointer;">{{ __('Save') }}</button>
                    @if ($has_stored_settings)
                        <button
                            type="submit"
                            formaction="{{ cp_route('utilities.noindex-redirect.reset') }}"
                            formnovalidate
                            style="padding:.5rem 1rem;border:1px solid rgba(127,127,127,.4);border-radius:.375rem;background:transparent;color:inherit;cursor:pointer;"
                        >{{ __('Reset to config') }}</button>
                    @endif
                </div>
            </form>
        </div>

        <p style="font-size:.75rem;opacity:.6;margin-top:.75rem;">
            {{ __('Stored overrides file: :path', ['path' => $storage_relative_path]) }}
            @if ($has_stored_settings)
                ({{ __('active') }})
            @else
                ({{ __('not set') }})
            @endif
        </p>

    </div>

@endsection
