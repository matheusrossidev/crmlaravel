@extends('tenant.layouts.app')
@php
    $title    = __('settings.pwa_title');
    $pageIcon = 'download';
@endphp

@push('styles')
<style>
.pwa-card {
    background: #fff; border-radius: 14px; border: 1.5px solid #e8eaf0;
    overflow: hidden; margin-bottom: 20px;
}
.pwa-card-header {
    padding: 16px 22px; border-bottom: 1px solid #f0f2f7;
    display: flex; align-items: center; gap: 10px;
    font-size: 14px; font-weight: 700; color: #1a1d23;
}
.pwa-card-header i { color: #0085f3; font-size: 16px; }
.pwa-card-body { padding: 24px; }

.pwa-hero {
    text-align: center; padding: 32px 24px;
}
.pwa-hero-icon {
    width: 72px; height: 72px; border-radius: 18px; background: #eff6ff;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 32px; color: #0085f3; margin-bottom: 16px;
}
.pwa-hero h2 {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 20px; font-weight: 700; color: #1a1d23; margin: 0 0 8px;
}
.pwa-hero p { font-size: 14px; color: #6b7280; margin: 0 0 24px; max-width: 480px; display: inline-block; line-height: 1.6; }

.pwa-install-btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 12px 28px; background: #0085f3; color: #fff; border: none;
    border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer;
    transition: background .15s;
}
.pwa-install-btn:hover { background: #0070d1; }
.pwa-install-btn i { font-size: 16px; }

.pwa-installed-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 10px 20px; background: #ecfdf5; color: #059669;
    border-radius: 10px; font-size: 14px; font-weight: 600;
}

.pwa-platforms {
    display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;
}
@media (max-width: 900px) { .pwa-platforms { grid-template-columns: 1fr 1fr; } }
@media (max-width: 500px) { .pwa-platforms { grid-template-columns: 1fr; } }
.pwa-platform {
    padding: 18px; border: 1.5px solid #e8eaf0; border-radius: 12px; text-align: center;
}
.pwa-platform i { font-size: 24px; color: #0085f3; display: block; margin-bottom: 8px; }
.pwa-platform h4 { font-size: 13px; font-weight: 700; color: #1a1d23; margin: 0 0 4px; }
.pwa-platform p { font-size: 12px; color: #6b7280; margin: 0; line-height: 1.5; }
</style>
@endpush

@section('content')
<div class="page-container">
    @include('tenant.settings._tabs')

    <div class="pwa-card">
        <div class="pwa-card-header">
            <i class="bi bi-download"></i>
            {{ __('settings.pwa_install_app') }}
        </div>
        <div class="pwa-card-body">
            <div class="pwa-hero">
                <div class="pwa-hero-icon"><i class="bi bi-phone"></i></div>
                <h2>{{ __('settings.pwa_install_title') }}</h2>
                <p>{{ __('settings.pwa_install_desc') }}</p>

                <div id="pwaInstallArea">
                    {{-- Install button --}}
                    <button class="pwa-install-btn" id="pwaInstallBtn" onclick="installPWA()">
                        <i class="bi bi-download"></i> {{ __('settings.pwa_install_now') }}
                    </button>

                    {{-- Already installed badge (hidden by default) --}}
                    <div class="pwa-installed-badge" id="pwaInstalledBadge" style="display:none;">
                        <i class="bi bi-check-circle-fill"></i> {{ __('settings.pwa_already_installed') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Instructions per platform --}}
    <div class="pwa-card">
        <div class="pwa-card-header">
            <i class="bi bi-question-circle"></i>
            {{ __('settings.pwa_how_to_install') }}
        </div>
        <div class="pwa-card-body">
            <div class="pwa-platforms">
                <div class="pwa-platform">
                    <i class="bi bi-browser-chrome"></i>
                    <h4>Chrome / Edge (PC)</h4>
                    <p>{{ __('settings.pwa_chrome_steps') }}</p>
                </div>
                <div class="pwa-platform">
                    <i class="bi bi-phone"></i>
                    <h4>Android</h4>
                    <p>{{ __('settings.pwa_android_steps') }}</p>
                </div>
                <div class="pwa-platform">
                    <i class="bi bi-apple"></i>
                    <h4>iPhone / iPad</h4>
                    <p>{{ __('settings.pwa_ios_steps') }}</p>
                </div>
                <div class="pwa-platform">
                    <i class="bi bi-browser-safari"></i>
                    <h4>Safari (Mac)</h4>
                    <p>{{ __('settings.pwa_safari_steps') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    let deferredPrompt = null;
    const installBtn = document.getElementById('pwaInstallBtn');
    const installedBadge = document.getElementById('pwaInstalledBadge');

    // Check if running as installed PWA
    function isInstalled() {
        return window.matchMedia('(display-mode: standalone)').matches
            || window.navigator.standalone === true;
    }

    if (isInstalled()) {
        installBtn.style.display = 'none';
        installedBadge.style.display = 'inline-flex';
    }

    // Capture install prompt when browser offers it
    window.addEventListener('beforeinstallprompt', function(e) {
        e.preventDefault();
        deferredPrompt = e;
    });

    window.addEventListener('appinstalled', function() {
        installBtn.style.display = 'none';
        installedBadge.style.display = 'inline-flex';
        deferredPrompt = null;
    });

    window.installPWA = function() {
        if (deferredPrompt) {
            // Browser supports install prompt
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then(function(result) {
                if (result.outcome === 'accepted') {
                    toastr.success('{{ __("settings.pwa_install_success") }}');
                }
                deferredPrompt = null;
            });
        } else if (isInstalled()) {
            toastr.info('{{ __("settings.pwa_already_installed") }}');
        } else {
            // Browser doesn't support automatic install - show manual hint
            toastr.info('{{ __("settings.pwa_manual_hint") }}');
        }
    };
})();
</script>
@endpush
