@extends('tenant.layouts.app')
@php
    $title    = __('settings.profile_title');
    $pageIcon = 'person-circle';
@endphp

@push('styles')
<style>
    .profile-layout {
        display: grid;
        grid-template-columns: 1fr 320px;
        gap: 22px;
        align-items: start;
    }
    @media (max-width: 860px) { .profile-layout { grid-template-columns: 1fr; } }

    .profile-card {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e8eaf0;
        overflow: hidden;
        margin-bottom: 20px;
    }
    .profile-card:last-child { margin-bottom: 0; }

    .profile-card-header {
        padding: 16px 22px;
        border-bottom: 1px solid #f0f2f7;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 14px;
        font-weight: 700;
        color: #1a1d23;
    }

    .profile-card-body { padding: 22px; }

    .form-group { margin-bottom: 16px; }
    .form-group label {
        display: block;
        font-size: 12.5px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 6px;
    }
    .form-control {
        width: 100%;
        padding: 9px 12px;
        border: 1px solid #d1d5db;
        border-radius: 9px;
        font-size: 13.5px;
        color: #1a1d23;
        outline: none;
        transition: border-color .15s;
        background: #fff;
    }
    .form-control:focus { border-color: #3B82F6; box-shadow: 0 0 0 3px rgba(59,130,246,.1); }
    .form-error { font-size: 12px; color: #EF4444; margin-top: 4px; }

    .btn-save {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 9px 20px;
        background: #0085f3;
        color: #fff;
        border: none;
        border-radius: 100px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: background .15s;
    }
    .btn-save:hover { background: #0070d1; }
    .btn-save:disabled { opacity: .6; cursor: not-allowed; }

    /* Avatar card */
    .avatar-wrap {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 14px;
        padding: 8px 0 4px;
    }

    .avatar-preview {
        width: 96px;
        height: 96px;
        border-radius: 50%;
        background: linear-gradient(135deg, #10B981, #3B82F6);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 32px;
        font-weight: 700;
        overflow: hidden;
        flex-shrink: 0;
        border: 3px solid #e8eaf0;
    }

    .avatar-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .avatar-upload-zone {
        width: 100%;
        border: 2px dashed #d1d5db;
        border-radius: 10px;
        padding: 18px;
        text-align: center;
        cursor: pointer;
        transition: border-color .15s, background .15s;
    }
    .avatar-upload-zone:hover { border-color: #3B82F6; background: #f0f7ff; }

    .avatar-upload-zone input[type=file] { display: none; }

    .avatar-upload-zone .upload-hint {
        font-size: 12px;
        color: #9ca3af;
        margin-top: 4px;
    }
</style>
@endpush

@section('content')
<div class="page-container">

    @include('tenant.settings._tabs')

    <div class="profile-layout">

        {{-- ── Coluna esquerda: Info + Senha ── --}}
        <div>

            {{-- Card: Informações Pessoais --}}
            <div class="profile-card">
                <div class="profile-card-header">
                    <i class="bi bi-person" style="color:#3B82F6;"></i>
                    {{ __('settings.profile_personal') }}
                </div>
                <div class="profile-card-body">
                    <form id="formProfile">
                        <div class="form-group">
                            <label>{{ __('settings.profile_name') }}</label>
                            <input type="text" class="form-control" id="profileName"
                                   value="{{ auth()->user()->name }}" placeholder="{{ __('settings.profile_name_ph') }}">
                            <div class="form-error d-none" id="errName"></div>
                        </div>
                        <div class="form-group">
                            <label>{{ __('settings.profile_email') }}</label>
                            <input type="email" class="form-control" id="profileEmail"
                                   value="{{ auth()->user()->email }}" placeholder="{{ __('settings.profile_email_ph') }}">
                            <div class="form-error d-none" id="errEmail"></div>
                        </div>
                        <div style="display:flex;align-items:center;gap:10px;margin-top:20px;">
                            <button type="submit" class="btn-save" id="btnProfile">
                                <i class="bi bi-check2"></i> {{ __('settings.profile_save_changes') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Card: Alterar Senha --}}
            <div class="profile-card">
                <div class="profile-card-header">
                    <i class="bi bi-lock" style="color:#3B82F6;"></i>
                    {{ __('settings.profile_password') }}
                </div>
                <div class="profile-card-body">
                    <form id="formPassword">
                        <div class="form-group">
                            <label>{{ __('settings.profile_current_pw') }}</label>
                            <input type="password" class="form-control" id="currentPassword" placeholder="{{ __('settings.profile_pw_ph') }}">
                            <div class="form-error d-none" id="errCurrentPwd"></div>
                        </div>
                        <div class="form-group">
                            <label>{{ __('settings.profile_new_pw') }}</label>
                            <input type="password" class="form-control" id="newPassword" placeholder="{{ __('settings.profile_new_pw_ph') }}">
                            <div class="form-error d-none" id="errNewPwd"></div>
                        </div>
                        <div class="form-group">
                            <label>{{ __('settings.profile_confirm_pw') }}</label>
                            <input type="password" class="form-control" id="confirmPassword" placeholder="{{ __('settings.profile_pw_ph') }}">
                        </div>
                        <button type="submit" class="btn-save" id="btnPassword" style="margin-top:4px;">
                            <i class="bi bi-shield-lock"></i> {{ __('settings.profile_change_pw') }}
                        </button>
                    </form>
                </div>
            </div>

            {{-- Card: Idioma --}}
            @if(auth()->user()->isAdmin())
            <div class="profile-card">
                <div class="profile-card-header">
                    <i class="bi bi-translate" style="color:#3B82F6;"></i>
                    {{ __('common.language') }}
                </div>
                <div class="profile-card-body">
                    <div class="form-group">
                        <label>{{ __('common.language') }}</label>
                        <select class="form-control" id="localeSelect" onchange="updateLocale(this.value)">
                            <option value="pt_BR" {{ (auth()->user()->tenant->locale ?? 'pt_BR') === 'pt_BR' ? 'selected' : '' }}>{{ __('common.portuguese') }}</option>
                            <option value="en" {{ (auth()->user()->tenant->locale ?? 'pt_BR') === 'en' ? 'selected' : '' }}>{{ __('common.english') }}</option>
                        </select>
                        <p style="font-size:11px;color:#9ca3af;margin-top:6px;">{{ __('settings.profile_lang_hint') }}</p>
                    </div>
                </div>
            </div>
            @endif

        </div>

        {{-- ── Coluna direita: Avatar ── --}}
        <div>
            <div class="profile-card">
                <div class="profile-card-header">
                    <i class="bi bi-image" style="color:#3B82F6;"></i>
                    {{ __('settings.profile_avatar') }}
                </div>
                <div class="profile-card-body">
                    <div class="avatar-wrap">
                        <div class="avatar-preview" id="avatarPreview">
                            @if(auth()->user()->avatar)
                                <img src="{{ auth()->user()->avatar }}" alt="Avatar" id="avatarImg">
                            @else
                                <span id="avatarInitial">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                            @endif
                        </div>

                        <label class="avatar-upload-zone" for="avatarFile">
                            <i class="bi bi-cloud-upload" style="font-size:22px;color:#9ca3af;"></i>
                            <div style="font-size:13px;font-weight:600;color:#374151;margin-top:6px;">
                                {{ __('settings.profile_upload') }}
                            </div>
                            <div class="upload-hint">{{ __('settings.profile_upload_hint') }}</div>
                            <input type="file" id="avatarFile" accept="image/*">
                        </label>
                    </div>
                </div>
            </div>

            {{-- Info da conta --}}
            <div class="profile-card">
                <div class="profile-card-header">
                    <i class="bi bi-info-circle" style="color:#3B82F6;"></i>
                    {{ __('settings.profile_account') }}
                </div>
                <div class="profile-card-body" style="font-size:13px;color:#6b7280;">
                    <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #f7f8fa;">
                        <span>{{ __('settings.profile_role') }}</span>
                        <span style="font-weight:600;color:#1a1d23;">{{ ucfirst(auth()->user()->role) }}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #f7f8fa;">
                        <span>{{ __('settings.profile_company') }}</span>
                        <span style="font-weight:600;color:#1a1d23;">{{ auth()->user()->tenant->name ?? '—' }}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;padding:6px 0;">
                        <span>{{ __('settings.profile_member_since') }}</span>
                        <span style="font-weight:600;color:#1a1d23;">{{ auth()->user()->created_at->format('d/m/Y') }}</span>
                    </div>
                </div>
            </div>

            {{-- Card: Agência Parceira (não mostra para parceiros) --}}
            @if(!auth()->user()->tenant?->isPartner())
            @php $agencyTenant = auth()->user()->tenant?->referringAgency; @endphp
            <div class="profile-card">
                <div class="profile-card-header">
                    <i class="bi bi-building-check" style="color:#3B82F6;"></i>
                    {{ __('settings.profile_agency') }}
                </div>
                <div class="profile-card-body">
                    @if($agencyTenant)
                        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;padding:6px 0;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <span style="display:inline-flex;align-items:center;gap:6px;background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;border-radius:20px;padding:4px 12px;font-size:12.5px;font-weight:600;">
                                    <i class="bi bi-check-circle-fill"></i> {{ __('settings.profile_agency_linked') }}
                                </span>
                                <span style="font-size:13.5px;font-weight:600;color:#1a1d23;">{{ $agencyTenant->name }}</span>
                            </div>
                            <div style="display:flex;gap:6px;">
                                <button onclick="confirmUnlinkPartner()" style="background:#fff;border:1.5px solid #fca5a5;color:#dc2626;border-radius:8px;padding:5px 12px;font-size:11.5px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:4px;">
                                    <i class="bi bi-x-circle"></i> Desvincular
                                </button>
                                <button onclick="document.getElementById('switchPartnerForm').style.display='block'" style="background:#eff6ff;border:1.5px solid #bfdbfe;color:#0085f3;border-radius:8px;padding:5px 12px;font-size:11.5px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:4px;">
                                    <i class="bi bi-arrow-left-right"></i> Trocar
                                </button>
                            </div>
                        </div>
                        <p style="font-size:12px;color:#9ca3af;margin:8px 0 0;">{{ __('settings.profile_agency_desc') }}</p>

                        {{-- Switch form (hidden) --}}
                        <div id="switchPartnerForm" style="display:none;margin-top:12px;padding:12px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:10px;">
                            <label style="font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;display:block;">Código da nova agência</label>
                            <div style="display:flex;gap:6px;">
                                <input id="switchAgencyCode" type="text" class="form-control"
                                       style="font-family:monospace;font-weight:700;letter-spacing:.06em;flex:1;text-transform:uppercase;"
                                       placeholder="EX: AGENCIA-123" maxlength="20">
                                <button onclick="confirmSwitchPartner()" class="btn-save" style="white-space:nowrap;">Confirmar</button>
                                <button onclick="document.getElementById('switchPartnerForm').style.display='none'" style="background:#f3f4f6;color:#6b7280;border:none;border-radius:8px;padding:6px 12px;font-size:12px;cursor:pointer;">Cancelar</button>
                            </div>
                        </div>
                    @else
                        <p style="font-size:13px;color:#6b7280;margin:0 0 14px;">{{ __('settings.profile_agency_none') }}</p>
                        <div style="display:flex;gap:8px;align-items:flex-start;">
                            <input type="text" id="agencyCodeInput"
                                   class="form-control" style="font-family:monospace;font-weight:700;letter-spacing:.06em;flex:1;"
                                   placeholder="{{ __('settings.profile_agency_ph') }}" maxlength="20"
                                   oninput="this.value=this.value.toUpperCase()">
                            <button id="btnLinkAgency" class="btn-save" style="white-space:nowrap;">
                                <i class="bi bi-link-45deg"></i> {{ __('settings.profile_link_agency') }}
                            </button>
                        </div>
                        <div class="form-error d-none" id="errAgency" style="margin-top:6px;"></div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Logo do Workspace (admin only) --}}
            @if(auth()->user()->isAdmin())
            <div class="profile-card">
                <div class="profile-card-header">
                    <i class="bi bi-building" style="color:#3B82F6;"></i>
                    {{ __('settings.profile_visual') }}
                </div>
                <div class="profile-card-body">
                    <div class="avatar-wrap">
                        <div class="avatar-preview" id="logoPreview"
                             style="border-radius:12px;background:linear-gradient(135deg,#3B82F6,#2563EB);">
                            @if(auth()->user()->tenant?->logo)
                                <img src="{{ auth()->user()->tenant->logo }}" alt="Logo" id="logoImg"
                                     style="width:100%;height:100%;object-fit:cover;border-radius:12px;">
                            @else
                                <span id="logoInitial" style="font-size:32px;">
                                    {{ strtoupper(substr(auth()->user()->tenant?->name ?? 'W', 0, 1)) }}
                                </span>
                            @endif
                        </div>

                        <label class="avatar-upload-zone" for="logoFile">
                            <i class="bi bi-cloud-upload" style="font-size:22px;color:#9ca3af;"></i>
                            <div style="font-size:13px;font-weight:600;color:#374151;margin-top:6px;">
                                {{ __('settings.profile_logo_upload') }}
                            </div>
                            <div class="upload-hint">{{ __('settings.profile_upload_hint') }}</div>
                            <input type="file" id="logoFile" accept="image/*">
                        </label>
                    </div>
                    <p style="font-size:11.5px;color:#9ca3af;margin-top:10px;text-align:center;">
                        {{ __('settings.profile_logo_hint') }}
                    </p>
                </div>
            </div>
            @endif

            {{-- Card: Tour --}}
            <div class="profile-card" style="margin-top:16px;">
                <div class="profile-card-header">
                    <i class="bi bi-signpost-2" style="color:#8B5CF6;"></i>
                    {{ app()->getLocale() === 'en' ? 'Platform Tour' : 'Tour da plataforma' }}
                </div>
                <div class="profile-card-body" style="padding:16px 20px;">
                    <p style="font-size:13px;color:#6b7280;margin:0 0 12px;">{{ app()->getLocale() === 'en' ? 'Redo the interactive tour to remember all features.' : 'Refaça o tour interativo para relembrar todas as funcionalidades.' }}</p>
                    <button class="btn-secondary-sm" onclick="resetTours()" style="font-size:12px;">
                        <i class="bi bi-arrow-counterclockwise"></i> {{ app()->getLocale() === 'en' ? 'Redo tour' : 'Refazer tour' }}
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
const SLANG = @json(__('settings'));
const profileUrl  = "{{ route('settings.profile.update') }}";
const passwordUrl = "{{ route('settings.profile.password') }}";
const avatarUrl   = "{{ route('settings.profile.avatar') }}";
const logoUrl     = "{{ route('settings.workspace.logo') }}";

// ── Perfil ────────────────────────────────────────────────
document.getElementById('formProfile').addEventListener('submit', async function(e) {
    e.preventDefault();
    clearErrors(['errName', 'errEmail']);
    const btn = document.getElementById('btnProfile');
    btn.disabled = true;

    try {
        const res = await fetch(profileUrl, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                name:  document.getElementById('profileName').value,
                email: document.getElementById('profileEmail').value,
            }),
        });
        const data = await res.json();
        if (res.ok && data.success) {
            toastr.success(data.message ?? SLANG.profile_updated);
        } else if (data.errors) {
            showErrors(data.errors, {name: 'errName', email: 'errEmail'});
        } else {
            toastr.error(data.message ?? SLANG.profile_update_error);
        }
    } catch { toastr.error(SLANG.profile_conn_error); }
    btn.disabled = false;
});

// ── Senha ─────────────────────────────────────────────────
document.getElementById('formPassword').addEventListener('submit', async function(e) {
    e.preventDefault();
    clearErrors(['errCurrentPwd', 'errNewPwd']);
    const btn = document.getElementById('btnPassword');
    btn.disabled = true;

    try {
        const res = await fetch(passwordUrl, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                current_password:       document.getElementById('currentPassword').value,
                password:               document.getElementById('newPassword').value,
                password_confirmation:  document.getElementById('confirmPassword').value,
            }),
        });
        const data = await res.json();
        if (res.ok && data.success) {
            toastr.success(data.message ?? SLANG.profile_pw_changed);
            document.getElementById('formPassword').reset();
        } else if (data.errors) {
            showErrors(data.errors, {current_password: 'errCurrentPwd', password: 'errNewPwd'});
        } else {
            toastr.error(data.message ?? SLANG.profile_pw_error);
        }
    } catch { toastr.error(SLANG.profile_conn_error); }
    btn.disabled = false;
});

// ── Avatar ────────────────────────────────────────────────
document.getElementById('avatarFile').addEventListener('change', async function() {
    const file = this.files[0];
    if (!file) return;

    if (file.size > 2 * 1024 * 1024) {
        toastr.error(SLANG.profile_avatar_large);
        return;
    }

    const fd = new FormData();
    fd.append('avatar', file);
    fd.append('_token', document.querySelector('meta[name=csrf-token]').content);

    try {
        const res = await fetch(avatarUrl, { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (res.ok && data.success) {
            toastr.success(data.message ?? SLANG.profile_avatar_ok);
            // Atualiza preview
            const preview = document.getElementById('avatarPreview');
            preview.innerHTML = `<img src="${data.avatar_url}?t=${Date.now()}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;">`;
            // Atualiza avatares do layout
            document.querySelectorAll('.user-avatar').forEach(el => {
                el.style.background = 'transparent';
                el.innerHTML = `<img src="${data.avatar_url}?t=${Date.now()}" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;">`;
            });
        } else {
            toastr.error(data.message ?? SLANG.profile_avatar_error);
        }
    } catch { toastr.error(SLANG.profile_conn_error); }
});

// ── Logo do Workspace ─────────────────────────────────────
const logoFileInput = document.getElementById('logoFile');
if (logoFileInput) {
    logoFileInput.addEventListener('change', async function() {
        const file = this.files[0];
        if (!file) return;

        if (file.size > 2 * 1024 * 1024) {
            toastr.error(SLANG.profile_avatar_large);
            return;
        }

        const fd = new FormData();
        fd.append('logo', file);
        fd.append('_token', document.querySelector('meta[name=csrf-token]').content);

        try {
            const res = await fetch(logoUrl, { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            if (res.ok && data.success) {
                toastr.success(data.message ?? SLANG.profile_logo_ok);
                // Atualiza preview
                const preview = document.getElementById('logoPreview');
                preview.style.background = 'transparent';
                preview.innerHTML = `<img src="${data.logo_url}?t=${Date.now()}" alt="Logo"
                    style="width:100%;height:100%;object-fit:cover;border-radius:12px;">`;
                // Atualiza workspace-avatar no sidebar
                document.querySelectorAll('.workspace-avatar').forEach(el => {
                    el.style.background = 'transparent';
                    el.innerHTML = `<img src="${data.logo_url}?t=${Date.now()}"
                        style="width:100%;height:100%;object-fit:cover;border-radius:8px;" alt="">`;
                });
            } else {
                toastr.error(data.message ?? SLANG.profile_logo_error);
            }
        } catch { toastr.error(SLANG.profile_conn_error); }
    });
}

// ── Vincular Agência Parceira ──────────────────────────────
const btnLinkAgency = document.getElementById('btnLinkAgency');
if (btnLinkAgency) {
    btnLinkAgency.addEventListener('click', async function() {
        const code = document.getElementById('agencyCodeInput').value.trim();
        const errEl = document.getElementById('errAgency');
        errEl.classList.add('d-none');
        errEl.textContent = '';

        if (!code) {
            errEl.textContent = SLANG.profile_agency_required;
            errEl.classList.remove('d-none');
            return;
        }

        btnLinkAgency.disabled = true;
        try {
            const res = await fetch("{{ route('settings.agency.link') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ agency_code: code }),
            });
            const data = await res.json();
            if (res.ok && data.success) {
                toastr.success(data.message ?? SLANG.profile_agency_ok);
                setTimeout(() => location.reload(), 1000);
            } else {
                errEl.textContent = data.message ?? SLANG.profile_agency_error;
                errEl.classList.remove('d-none');
            }
        } catch { toastr.error(SLANG.profile_conn_error); }
        btnLinkAgency.disabled = false;
    });
}

// ── Desvincular / Trocar Parceiro ────────────────────────────
const _csrf = document.querySelector('meta[name=csrf-token]').content;

function confirmUnlinkPartner() {
    window.confirmAction({
        title: 'Desvincular agência parceira?',
        message: 'Comissões pendentes (em carência) serão canceladas. Comissões já liberadas serão mantidas.',
        confirmText: 'Desvincular',
        onConfirm: doUnlinkPartner,
    });
}

async function doUnlinkPartner() {
    try {
        const res = await fetch("{{ route('settings.agency.unlink') }}", {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': _csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        });
        const data = await res.json();
        if (data.success) {
            toastr.success(data.message);
            setTimeout(() => window.location.reload(), 1500);
        } else {
            toastr.error(data.message || 'Erro ao desvincular.');
        }
    } catch { toastr.error('Erro de conexão.'); }
}

function confirmSwitchPartner() {
    const code = document.getElementById('switchAgencyCode').value.trim();
    if (!code) { toastr.warning('Informe o código da agência.'); return; }
    window.confirmAction({
        title: 'Trocar agência parceira?',
        message: 'Comissões pendentes do parceiro atual serão canceladas. Novas comissões irão para o novo parceiro.',
        confirmText: 'Trocar',
        onConfirm: () => doSwitchPartner(code),
    });
}

async function doSwitchPartner(code) {
    try {
        const res = await fetch("{{ route('settings.agency.switch') }}", {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': _csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ agency_code: code }),
        });
        const data = await res.json();
        if (data.success) {
            toastr.success(data.message);
            setTimeout(() => window.location.reload(), 1500);
        } else {
            toastr.error(data.message || 'Erro ao trocar parceiro.');
        }
    } catch { toastr.error('Erro de conexão.'); }
}

// ── Helpers ───────────────────────────────────────────────
function clearErrors(ids) {
    ids.forEach(id => {
        const el = document.getElementById(id);
        if (el) { el.textContent = ''; el.classList.add('d-none'); }
    });
}

function showErrors(errors, map) {
    Object.keys(map).forEach(field => {
        if (errors[field]) {
            const el = document.getElementById(map[field]);
            if (el) { el.textContent = errors[field][0]; el.classList.remove('d-none'); }
        }
    });
}

// ── Locale ───────────────────────────────────────────────
function resetTours() {
    window.API.post('{{ route("tour.reset") }}')
        .then(function() {
            toastr.success('{{ app()->getLocale() === "en" ? "Tours reset! Redirecting..." : "Tours resetados! Redirecionando..." }}');
            setTimeout(function() { window.location.href = '{{ route("dashboard") }}'; }, 1000);
        })
        .catch(function() { toastr.error('Erro'); });
}

function updateLocale(locale) {
    window.API.put('{{ route("settings.profile.locale") }}', { locale: locale })
        .then(function() {
            toastr.success(SLANG.profile_locale_ok);
            setTimeout(function() { location.reload(); }, 500);
        })
        .catch(function() { toastr.error(SLANG.profile_locale_error); });
}
</script>
@endpush
