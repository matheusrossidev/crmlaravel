@extends('tenant.layouts.app')
@php
    $title = 'Campanhas';
    $pageIcon = 'megaphone';
@endphp

@push('styles')
<style>
    .campaigns-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
        gap: 18px;
    }

    .campaign-card {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e8eaf0;
        overflow: hidden;
        transition: box-shadow .15s;
    }

    .campaign-card:hover {
        box-shadow: 0 4px 20px rgba(0,0,0,.07);
    }

    .campaign-card-header {
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        border-bottom: 1px solid #f0f2f7;
    }

    .platform-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
        font-weight: 700;
        color: #fff;
    }

    .platform-icon.facebook { background: #1877F2; }
    .platform-icon.google   { background: linear-gradient(135deg, #4285F4, #EA4335, #FBBC04, #34A853); }

    .campaign-name {
        font-size: 14px;
        font-weight: 700;
        color: #1a1d23;
        flex: 1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .campaign-name small {
        display: block;
        font-size: 11px;
        font-weight: 500;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-top: 1px;
    }

    .status-badge {
        font-size: 11px;
        font-weight: 600;
        padding: 3px 9px;
        border-radius: 99px;
        white-space: nowrap;
    }

    .status-active  { background: #d1fae5; color: #065f46; }
    .status-paused  { background: #fef3c7; color: #92400e; }
    .status-archived{ background: #f3f4f6; color: #6b7280; }

    .campaign-metrics {
        padding: 16px 20px;
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
    }

    .metric-item {
        text-align: center;
    }

    .metric-value {
        font-size: 17px;
        font-weight: 700;
        color: #1a1d23;
        line-height: 1;
        margin-bottom: 3px;
    }

    .metric-label {
        font-size: 10.5px;
        font-weight: 600;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .metric-item.green .metric-value { color: #10B981; }
    .metric-item.blue  .metric-value { color: #3B82F6; }

    .campaign-footer {
        padding: 10px 20px;
        border-top: 1px solid #f0f2f7;
        font-size: 11.5px;
        color: #9ca3af;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .empty-state {
        text-align: center;
        padding: 80px 20px;
        color: #9ca3af;
    }

    .empty-state i { font-size: 52px; opacity: .25; margin-bottom: 14px; display: block; }
    .empty-state h3 { font-size: 16px; color: #374151; margin: 0 0 6px; }
    .empty-state p  { font-size: 13.5px; margin: 0; }
</style>
@endpush

@section('content')
<div class="page-container">

    @if($campaigns->isEmpty())
    <div class="empty-state">
        <i class="bi bi-megaphone"></i>
        <h3>Nenhuma campanha ativa</h3>
        <p>
            Conecte uma integração com Facebook Ads ou Google Ads para visualizar suas campanhas.<br>
            <a href="{{ route('settings.integrations.index') }}" style="color:#3B82F6;font-weight:600;">
                Ir para Integrações →
            </a>
        </p>
    </div>
    @else
    <div class="campaigns-grid">
        @foreach($campaigns as $item)
        @php
            $campaign = $item['campaign'];
            $platform = $campaign->platform;
        @endphp
        <div class="campaign-card">
            <div class="campaign-card-header">
                <div class="platform-icon {{ $platform }}">
                    @if($platform === 'facebook')
                        f
                    @else
                        G
                    @endif
                </div>
                <div class="campaign-name">
                    {{ $campaign->name }}
                    <small>{{ $platform === 'facebook' ? 'Facebook Ads' : 'Google Ads' }}</small>
                </div>
                <span class="status-badge status-{{ $campaign->status }}">
                    {{ $campaign->status === 'active' ? 'Ativo' : ucfirst($campaign->status) }}
                </span>
            </div>

            <div class="campaign-metrics">
                <div class="metric-item green">
                    <div class="metric-value">
                        R$ {{ $item['total_spend'] > 0 ? number_format($item['total_spend'], 0, ',', '.') : '—' }}
                    </div>
                    <div class="metric-label">Investido</div>
                </div>

                <div class="metric-item">
                    <div class="metric-value">
                        {{ $item['total_impressions'] > 0 ? number_format($item['total_impressions'], 0, ',', '.') : '—' }}
                    </div>
                    <div class="metric-label">Impressões</div>
                </div>

                <div class="metric-item">
                    <div class="metric-value">
                        {{ $item['total_clicks'] > 0 ? number_format($item['total_clicks'], 0, ',', '.') : '—' }}
                    </div>
                    <div class="metric-label">Cliques</div>
                </div>

                <div class="metric-item">
                    <div class="metric-value">
                        {{ $item['ctr'] !== null ? $item['ctr'] . '%' : '—' }}
                    </div>
                    <div class="metric-label">CTR</div>
                </div>

                <div class="metric-item blue">
                    <div class="metric-value">{{ $item['leads_count'] }}</div>
                    <div class="metric-label">Leads</div>
                </div>

                <div class="metric-item">
                    <div class="metric-value">
                        {{ $item['cost_per_lead'] !== null ? 'R$ ' . number_format($item['cost_per_lead'], 2, ',', '.') : '—' }}
                    </div>
                    <div class="metric-label">Custo/Lead</div>
                </div>
            </div>

            <div class="campaign-footer">
                <span>
                    @if($campaign->last_sync_at)
                        Sync: {{ $campaign->last_sync_at->diffForHumans() }}
                    @else
                        Nunca sincronizado
                    @endif
                </span>
                @if($campaign->budget_daily)
                <span>Orçamento: R$ {{ number_format((float)$campaign->budget_daily, 2, ',', '.') }}/dia</span>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif

</div>
@endsection
