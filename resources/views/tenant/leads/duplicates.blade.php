@extends('tenant.layouts.app')

@php
    $title = __('leads.duplicates_title');
    $pageIcon = 'copy';
@endphp

@push('styles')
<style>
    .dup-card {
        background: #fff;
        border-radius: 14px;
        border: 1.5px solid #e8eaf0;
        overflow: hidden;
    }
    .dup-card-header {
        padding: 16px 22px;
        border-bottom: 1px solid #f0f2f7;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .dup-card-header h3 {
        font-size: 14px;
        font-weight: 700;
        color: #1a1d23;
        margin: 0;
    }
    .dup-table-wrap { overflow-x: auto; }

    .dup-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13.5px;
    }
    .dup-table thead th {
        padding: 12px 16px;
        font-size: 11.5px;
        font-weight: 700;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: .06em;
        border-bottom: 1px solid #f0f2f7;
        background: #fafafa;
        white-space: nowrap;
    }
    .dup-table tbody tr {
        border-bottom: 1px solid #f7f8fa;
        transition: background .12s;
    }
    .dup-table tbody tr:hover { background: #f8faff; }
    .dup-table tbody tr:last-child { border-bottom: none; }
    .dup-table tbody td {
        padding: 12px 16px;
        color: #374151;
        vertical-align: middle;
    }

    .score-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .score-high { background: #fef2f2; color: #dc2626; }
    .score-medium { background: #fefce8; color: #ca8a04; }
    .score-low { background: #f0f9ff; color: #0284c7; }

    .dup-lead-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .dup-lead-name {
        font-weight: 600;
        color: #1a1d23;
    }
    .dup-lead-detail {
        font-size: 12px;
        color: #6b7280;
    }

    .dup-vs {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        background: #f3f4f6;
        border-radius: 50%;
        font-size: 11px;
        font-weight: 700;
        color: #9ca3af;
    }

    .dup-actions {
        display: flex;
        gap: 8px;
    }

    .btn-merge {
        background: #0085f3;
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 6px 14px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: background .15s;
    }
    .btn-merge:hover { background: #0070d1; color: #fff; }

    .btn-ignore {
        background: #f3f4f6;
        color: #6b7280;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 6px 14px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: background .15s;
    }
    .btn-ignore:hover { background: #e5e7eb; }

    .dup-filters {
        display: flex;
        gap: 12px;
        align-items: center;
    }
    .dup-filter-btn {
        padding: 6px 14px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        border: 1.5px solid #e8eaf0;
        background: #fff;
        color: #374151;
        cursor: pointer;
        transition: all .15s;
    }
    .dup-filter-btn.active {
        background: #eff6ff;
        color: #0085f3;
        border-color: #bfdbfe;
    }

    .dup-empty {
        text-align: center;
        padding: 60px 20px;
        color: #9ca3af;
    }
    .dup-empty .material-symbols-rounded {
        font-size: 48px;
        margin-bottom: 12px;
        color: #d1d5db;
    }

    .dup-count-badge {
        background: #fef2f2;
        color: #dc2626;
        padding: 2px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    /* Merge Modal */
    .merge-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,.5);
        z-index: 1050;
        display: none;
        align-items: center;
        justify-content: center;
    }
    .merge-modal-overlay.active { display: flex; }
    .merge-modal {
        background: #fff;
        border-radius: 16px;
        width: 95%;
        max-width: 700px;
        max-height: 85vh;
        overflow-y: auto;
        padding: 0;
    }
    .merge-modal-header {
        padding: 20px 24px;
        border-bottom: 1px solid #f0f2f7;
    }
    .merge-modal-header h3 {
        font-size: 16px;
        font-weight: 700;
        color: #1a1d23;
        margin: 0;
    }
    .merge-modal-body { padding: 20px 24px; }
    .merge-modal-footer {
        padding: 16px 24px;
        border-top: 1px solid #f0f2f7;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }
    .merge-compare {
        display: grid;
        grid-template-columns: 1fr auto 1fr;
        gap: 16px;
        align-items: start;
    }
    .merge-lead-card {
        background: #f9fafb;
        border-radius: 12px;
        padding: 16px;
        border: 1.5px solid #e8eaf0;
    }
    .merge-lead-card.primary { border-color: #0085f3; background: #f0f7ff; }
    .merge-lead-card h4 {
        font-size: 13px;
        font-weight: 700;
        color: #1a1d23;
        margin: 0 0 12px;
    }
    .merge-field {
        display: flex;
        justify-content: space-between;
        padding: 6px 0;
        font-size: 12.5px;
        border-bottom: 1px solid #f0f2f7;
    }
    .merge-field:last-child { border-bottom: none; }
    .merge-field-label { color: #6b7280; font-weight: 600; }
    .merge-field-value { color: #1a1d23; text-align: right; }
    .merge-field-value.empty { color: #d1d5db; font-style: italic; }
    .merge-field-value.will-fill { color: #059669; font-weight: 600; }
    .merge-vs-divider {
        display: flex;
        align-items: center;
        justify-content: center;
        padding-top: 40px;
    }
    .merge-relations {
        margin-top: 16px;
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-radius: 10px;
        padding: 12px 16px;
        font-size: 12.5px;
        color: #92400e;
    }
    .merge-relations strong { font-weight: 700; }

    @media (max-width: 768px) {
        .merge-compare {
            grid-template-columns: 1fr;
        }
        .merge-vs-divider { padding-top: 0; }
        .dup-actions { flex-direction: column; }
    }
</style>
@endpush

@section('content')
<div class="page-container">
    {{-- Header --}}
    <div style="margin-bottom:20px;">
        <div style="font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:#97A3B7;margin-bottom:4px;">CRM</div>
        <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
        <div>
            <h1 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:22px;font-weight:700;color:#1a1d23;margin:0 0 4px;">{{ __('leads.duplicates_heading') }}</h1>
            <p style="font-size:13.5px;color:#677489;margin:0;">{{ __('leads.duplicates_subtitle') }}</p>
        </div>
        <div class="dup-filters">
            <button class="dup-filter-btn active" data-status="pending">
                {{ __('leads.status_pending') }} <span id="pendingBadge" class="dup-count-badge" style="margin-left:4px;">{{ $pendingCount }}</span>
            </button>
            <button class="dup-filter-btn" data-status="ignored">{{ __('leads.status_ignored') }}</button>
            <button class="dup-filter-btn" data-status="merged">{{ __('leads.status_merged') }}</button>
        </div>
    </div>
    </div>

    {{-- Table --}}
    <div class="dup-card">
        <div class="dup-table-wrap">
            <table class="dup-table">
                <thead>
                    <tr>
                        <th>Lead A</th>
                        <th></th>
                        <th>Lead B</th>
                        <th style="text-align:center;">Score</th>
                        <th>{{ __('leads.col_detected_by') }}</th>
                        <th>{{ __('leads.date_label') }}</th>
                        <th style="text-align:right;">{{ __('leads.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody id="dupTableBody">
                    <tr>
                        <td colspan="7">
                            <div style="text-align:center; padding:40px; color:#9ca3af;">
                                <div class="spinner-border spinner-border-sm" role="status"></div>
                                {{ __('leads.loading') }}
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div id="dupEmpty" class="dup-empty" style="display:none;">
            <i class="bi bi-check-circle" style="font-size:48px; color:#d1d5db;"></i>
            <div style="font-size:15px; font-weight:600; color:#374151; margin-bottom:4px;">{{ __('leads.no_duplicates') }}</div>
            <div style="font-size:13px;">{{ __('leads.leads_clean') }}</div>
        </div>
    </div>
</div>

{{-- Merge Preview Modal --}}
<div class="merge-modal-overlay" id="mergeModal">
    <div class="merge-modal">
        <div class="merge-modal-header">
            <h3>{{ __('leads.merge_preview_title') }}</h3>
        </div>
        <div class="merge-modal-body" id="mergeModalBody">
            <div style="text-align:center; padding:40px; color:#9ca3af;">
                <div class="spinner-border spinner-border-sm" role="status"></div>
                {{ __('leads.loading_preview') }}
            </div>
        </div>
        <div class="merge-modal-footer">
            <button class="btn-ignore" onclick="closeMergeModal()">{{ __('common.cancel') }}</button>
            <button class="btn-merge" id="btnConfirmMerge" onclick="confirmMerge()">
                <i class="bi bi-intersect" style="font-size:13px;"></i> {{ __('leads.confirm_merge') }}
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    const BASE = @json(url('/'));
    const DLANG = {
        loading: @json(__('leads.loading')),
        error_loading: @json(__('leads.error_loading_dups')),
        detected_realtime: @json(__('leads.detected_realtime')),
        detected_import: @json(__('leads.detected_import')),
        detected_scheduled: @json(__('leads.detected_scheduled')),
        merge_btn: @json(__('leads.merge_btn')),
        ignore_btn: @json(__('leads.ignore_btn')),
        merged_label: @json(__('leads.merged_label')),
        ignored_label: @json(__('leads.ignored_label')),
        loading_preview: @json(__('leads.loading_preview')),
        error_preview: @json(__('leads.merge_error')),
        merging: @json(__('leads.merging')),
        merge_success: @json(__('leads.merge_success')),
        merge_error: @json(__('leads.merge_error')),
        confirm_merge: @json(__('leads.confirm_merge')),
        pair_ignored: @json(__('leads.pair_ignored')),
        error_ignoring: @json(__('leads.error_ignoring')),
        merge_lead_a: @json(__('leads.merge_lead_a')),
        merge_lead_b: @json(__('leads.merge_lead_b')),
        merge_explanation: @json(__('leads.merge_explanation')),
        tags_to_add: @json(__('leads.tags_to_add')),
        empty_field: @json(__('leads.empty_field')),
        rel: {
            notes: @json(__('leads.rel_notes')), attachments: @json(__('leads.rel_attachments')),
            tasks: @json(__('leads.rel_tasks')), contacts: @json(__('leads.rel_contacts')),
            products: @json(__('leads.rel_products')), events: @json(__('leads.rel_events')),
            sales: @json(__('leads.rel_sales')), lost_sales: @json(__('leads.rel_lost_sales')),
            whatsapp: @json(__('leads.rel_whatsapp')), instagram: @json(__('leads.rel_instagram')),
            website: @json(__('leads.rel_website')), custom_fields: @json(__('leads.rel_custom_fields')),
            score_logs: @json(__('leads.rel_score_logs')), sequences: @json(__('leads.rel_sequences')),
            scheduled_msgs: @json(__('leads.rel_scheduled_msgs')),
        },
    };
    let currentStatus = 'pending';
    let mergePrimaryId = null;
    let mergeSecondaryId = null;

    // Filter buttons
    document.querySelectorAll('.dup-filter-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.dup-filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentStatus = btn.dataset.status;
            loadDuplicates();
        });
    });

    function loadDuplicates() {
        const tbody = document.getElementById('dupTableBody');
        const empty = document.getElementById('dupEmpty');
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:40px; color:#9ca3af;"><div class="spinner-border spinner-border-sm" role="status"></div> ' + DLANG.loading + '</td></tr>';
        empty.style.display = 'none';

        window.API.get(`${BASE}/contatos/duplicatas/data?status=${currentStatus}`)
            .then(res => {
                if (!res.data || res.data.length === 0) {
                    tbody.innerHTML = '';
                    empty.style.display = 'block';
                    return;
                }

                empty.style.display = 'none';
                tbody.innerHTML = res.data.map(dup => renderRow(dup)).join('');
            })
            .catch(() => {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:20px; color:#dc2626;">' + DLANG.error_loading + '</td></tr>';
            });
    }

    function renderRow(dup) {
        const a = dup.lead_a || {};
        const b = dup.lead_b || {};
        const scoreClass = dup.score >= 70 ? 'score-high' : dup.score >= 50 ? 'score-medium' : 'score-low';
        const detectedLabels = { realtime: DLANG.detected_realtime, import: DLANG.detected_import, scheduled_job: DLANG.detected_scheduled };
        const detected = detectedLabels[dup.detected_by] || dup.detected_by;
        const date = dup.created_at ? new Date(dup.created_at).toLocaleDateString('pt-BR') : '—';

        let actions = '';
        if (currentStatus === 'pending') {
            actions = `
                <button class="btn-merge" onclick="openMergeModal(${a.id}, ${b.id})">
                    ${DLANG.merge_btn}
                </button>
                <button class="btn-ignore" onclick="ignoreDuplicate(${dup.id}, this)">${DLANG.ignore_btn}</button>
            `;
        } else if (currentStatus === 'merged') {
            actions = '<span style="color:#059669; font-size:12px; font-weight:600;">' + DLANG.merged_label + '</span>';
        } else {
            actions = '<span style="color:#9ca3af; font-size:12px;">' + DLANG.ignored_label + '</span>';
        }

        return `<tr>
            <td>
                <div class="dup-lead-info">
                    <a href="${BASE}/contatos/${a.id}/perfil" class="dup-lead-name" target="_blank">${esc(a.name || '—')}</a>
                    <span class="dup-lead-detail">${esc(a.phone || '')} ${a.email ? '· ' + esc(a.email) : ''}</span>
                    ${a.stage ? '<span class="dup-lead-detail">' + esc(a.stage.name) + '</span>' : ''}
                </div>
            </td>
            <td style="text-align:center;"><span class="dup-vs">VS</span></td>
            <td>
                <div class="dup-lead-info">
                    <a href="${BASE}/contatos/${b.id}/perfil" class="dup-lead-name" target="_blank">${esc(b.name || '—')}</a>
                    <span class="dup-lead-detail">${esc(b.phone || '')} ${b.email ? '· ' + esc(b.email) : ''}</span>
                    ${b.stage ? '<span class="dup-lead-detail">' + esc(b.stage.name) + '</span>' : ''}
                </div>
            </td>
            <td style="text-align:center;"><span class="score-badge ${scoreClass}">${dup.score}%</span></td>
            <td style="font-size:12px; color:#6b7280;">${detected}</td>
            <td style="font-size:12px; color:#6b7280;">${date}</td>
            <td style="text-align:right;"><div class="dup-actions">${actions}</div></td>
        </tr>`;
    }

    function esc(str) {
        return window.escapeHtml ? window.escapeHtml(str) : String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    window.openMergeModal = function(primaryId, secondaryId) {
        mergePrimaryId = primaryId;
        mergeSecondaryId = secondaryId;
        document.getElementById('mergeModal').classList.add('active');
        document.getElementById('mergeModalBody').innerHTML = '<div style="text-align:center; padding:40px; color:#9ca3af;"><div class="spinner-border spinner-border-sm" role="status"></div> ' + DLANG.loading_preview + '</div>';

        window.API.get(`${BASE}/contatos/${primaryId}/merge/${secondaryId}/preview`)
            .then(res => {
                renderMergePreview(res.data);
            })
            .catch(err => {
                document.getElementById('mergeModalBody').innerHTML = '<div style="text-align:center; padding:20px; color:#dc2626;">' + DLANG.error_preview + '</div>';
            });
    };

    function renderMergePreview(data) {
        const p = data.primary;
        const s = data.secondary;
        const fill = data.fields_to_fill || {};
        const rels = data.relations || {};
        const tags = data.tags_to_add || [];

        const fields = [
            { key: 'name', label: @json(__('leads.name')) },
            { key: 'phone', label: @json(__('leads.phone')) },
            { key: 'email', label: 'Email' },
            { key: 'company', label: @json(__('leads.company')) },
            { key: 'value', label: @json(__('leads.value')) },
            { key: 'source', label: @json(__('leads.source')) },
            { key: 'birthday', label: @json(__('leads.birthday')) },
        ];

        let primaryFields = '';
        let secondaryFields = '';
        fields.forEach(f => {
            const pVal = p[f.key] || '';
            const sVal = s[f.key] || '';
            const willFill = fill[f.key] !== undefined;
            primaryFields += `<div class="merge-field">
                <span class="merge-field-label">${f.label}</span>
                <span class="merge-field-value ${!pVal ? 'empty' : ''} ${willFill ? 'will-fill' : ''}">${pVal || (willFill ? '← ' + esc(String(sVal)) : DLANG.empty_field)}</span>
            </div>`;
            secondaryFields += `<div class="merge-field">
                <span class="merge-field-label">${f.label}</span>
                <span class="merge-field-value ${!sVal ? 'empty' : ''}">${sVal || DLANG.empty_field}</span>
            </div>`;
        });

        // Relations summary
        let relTotal = 0;
        let relItems = [];
        Object.entries(rels).forEach(([key, count]) => {
            if (count > 0) {
                relTotal += count;
                relItems.push(`<strong>${count}</strong> ${DLANG.rel[key] || key}`);
            }
        });

        let relHtml = '';
        if (relTotal > 0) {
            relHtml = `<div class="merge-relations">
                <strong>${relTotal}</strong> ${@json(__('leads.merge_relations_text', ['count' => ''])).replace(':count ', '')} ${relItems.join(', ')}
            </div>`;
        }

        let tagsHtml = '';
        if (tags.length > 0) {
            tagsHtml = `<div style="margin-top:12px; font-size:12.5px; color:#374151;">
                <strong>${DLANG.tags_to_add}</strong> ${tags.map(t => `<span style="background:#eff6ff; color:#0085f3; padding:2px 8px; border-radius:12px; font-size:11px; margin:0 2px;">${esc(t)}</span>`).join('')}
            </div>`;
        }

        document.getElementById('mergeModalBody').innerHTML = `
            <p style="font-size:13px; color:#6b7280; margin:0 0 16px;">
                ${DLANG.merge_explanation}
            </p>
            <div class="merge-compare">
                <div class="merge-lead-card primary">
                    <h4 style="color:#0085f3;">${DLANG.merge_lead_a}</h4>
                    ${primaryFields}
                </div>
                <div class="merge-vs-divider"><span class="dup-vs">→</span></div>
                <div class="merge-lead-card">
                    <h4>${DLANG.merge_lead_b}</h4>
                    ${secondaryFields}
                </div>
            </div>
            ${relHtml}
            ${tagsHtml}
        `;
    }

    window.closeMergeModal = function() {
        document.getElementById('mergeModal').classList.remove('active');
        mergePrimaryId = null;
        mergeSecondaryId = null;
    };

    window.confirmMerge = function() {
        if (!mergePrimaryId || !mergeSecondaryId) return;
        const btn = document.getElementById('btnConfirmMerge');
        btn.disabled = true;
        btn.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"></div> ' + DLANG.merging;

        window.API.post(`${BASE}/contatos/${mergePrimaryId}/merge/${mergeSecondaryId}`)
            .then(res => {
                closeMergeModal();
                toastr.success(res.message || DLANG.merge_success);
                loadDuplicates();
            })
            .catch(err => {
                toastr.error(err.responseJSON?.message || DLANG.merge_error);
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-intersect" style="font-size:13px;"></i> ' + DLANG.confirm_merge;
            });
    };

    window.ignoreDuplicate = function(dupId, btnEl) {
        btnEl.disabled = true;
        window.API.post(`${BASE}/contatos/duplicatas/${dupId}/ignorar`)
            .then(res => {
                toastr.success(res.message || DLANG.pair_ignored);
                loadDuplicates();
            })
            .catch(() => {
                toastr.error(DLANG.error_ignoring);
                btnEl.disabled = false;
            });
    };

    // Close modal on overlay click
    document.getElementById('mergeModal').addEventListener('click', function(e) {
        if (e.target === this) closeMergeModal();
    });

    // Initial load
    loadDuplicates();
})();
</script>
@endpush
