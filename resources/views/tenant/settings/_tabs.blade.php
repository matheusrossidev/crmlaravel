@php
    $isAdmin = auth()->user()->isAdmin() || auth()->user()->isSuperAdmin();
    $igConnected = auth()->check() && \App\Models\InstagramInstance::where('status', 'connected')->exists();

    $settingsTabs = [
        ['route' => 'settings.profile',             'match' => ['settings.profile*'],                        'label' => 'Perfil'],
        ['route' => 'settings.notifications',        'match' => ['settings.notifications*'],                 'label' => 'Notificações'],
        ['route' => 'settings.pipelines',            'match' => ['settings.pipelines*'],                     'label' => 'Pipelines'],
        ['route' => 'settings.lost-reasons',         'match' => ['settings.lost-reasons*'],                  'label' => 'Motivos de Perda'],
    ];

    if ($isAdmin) {
        $settingsTabs[] = ['route' => 'settings.users',          'match' => ['settings.users*'],             'label' => 'Usuários'];
        $settingsTabs[] = ['route' => 'settings.custom-fields',  'match' => ['settings.custom-fields*'],     'label' => 'Campos Extras'];
    }

    $settingsTabs[] = ['route' => 'settings.integrations.index', 'match' => ['settings.integrations*'],      'label' => 'Integrações'];

    if ($igConnected) {
        $settingsTabs[] = ['route' => 'settings.ig-automations.index', 'match' => ['settings.ig-automations*'], 'label' => 'Autom. Instagram'];
    }

    $settingsTabs[] = ['route' => 'settings.tags',               'match' => ['settings.tags*'],               'label' => 'Tags'];
    $settingsTabs[] = ['route' => 'settings.departments',        'match' => ['settings.departments*'],        'label' => 'Departamentos'];
    $settingsTabs[] = ['route' => 'settings.automations',        'match' => ['settings.automations*'],        'label' => 'Automações'];
    $settingsTabs[] = ['route' => 'settings.billing',            'match' => ['settings.billing*', 'billing.*'], 'label' => 'Cobrança'];
    $settingsTabs[] = ['route' => 'settings.api-keys',           'match' => ['settings.api-keys*'],           'label' => 'API / Webhooks'];
@endphp
<div class="settings-nav-tabs">
    @foreach($settingsTabs as $tab)
        <a href="{{ route($tab['route']) }}"
           class="settings-nav-tab {{ request()->routeIs(...$tab['match']) ? 'active' : '' }}">
            {{ $tab['label'] }}
        </a>
    @endforeach
</div>
