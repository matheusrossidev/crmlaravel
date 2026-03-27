@php
    $isAdmin = auth()->user()->isAdmin() || auth()->user()->isSuperAdmin();
    $igConnected = auth()->check() && \App\Models\InstagramInstance::where('status', 'connected')->exists();

    $settingsTabs = [
        ['route' => 'settings.profile',             'match' => ['settings.profile*'],                        'label' => __('settings.profile_title')],
        ['route' => 'settings.notifications',        'match' => ['settings.notifications*'],                 'label' => __('settings.notif_title')],
    ];

    if ($isAdmin) {
        $settingsTabs[] = ['route' => 'settings.users',          'match' => ['settings.users*'],             'label' => __('settings.users_title')];
    }

    $settingsTabs[] = ['route' => 'settings.integrations.index', 'match' => ['settings.integrations*'],      'label' => __('integrations.title')];

    $settingsTabs[] = ['route' => 'settings.departments',        'match' => ['settings.departments*'],        'label' => __('settings.dept_title')];
    $settingsTabs[] = ['route' => 'settings.billing',            'match' => ['settings.billing*', 'billing.*'], 'label' => __('settings.billing_title')];
    $settingsTabs[] = ['route' => 'settings.api-keys',           'match' => ['settings.api-keys*'],           'label' => __('apikeys.title')];
@endphp
<div class="settings-nav-tabs">
    @foreach($settingsTabs as $tab)
        <a href="{{ route($tab['route']) }}"
           class="settings-nav-tab {{ request()->routeIs(...$tab['match']) ? 'active' : '' }}">
            {{ $tab['label'] }}
        </a>
    @endforeach
</div>
