@extends('tenant.layouts.app')
@php
    $title    = 'Meu Perfil';
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
        background: #3B82F6;
        color: #fff;
        border: none;
        border-radius: 9px;
        font-size: 13.5px;
        font-weight: 600;
        cursor: pointer;
        transition: background .15s;
    }
    .btn-save:hover { background: #2563EB; }
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
    <div class="profile-layout">

        {{-- ── Coluna esquerda: Info + Senha ── --}}
        <div>

            {{-- Card: Informações Pessoais --}}
            <div class="profile-card">
                <div class="profile-card-header">
                    <i class="bi bi-person" style="color:#3B82F6;"></i>
                    Informações Pessoais
                </div>
                <div class="profile-card-body">
                    <form id="formProfile">
                        <div class="form-group">
                            <label>Nome completo</label>
                            <input type="text" class="form-control" id="profileName"
                                   value="{{ auth()->user()->name }}" placeholder="Seu nome">
                            <div class="form-error d-none" id="errName"></div>
                        </div>
                        <div class="form-group">
                            <label>E-mail</label>
                            <input type="email" class="form-control" id="profileEmail"
                                   value="{{ auth()->user()->email }}" placeholder="seu@email.com">
                            <div class="form-error d-none" id="errEmail"></div>
                        </div>
                        <div style="display:flex;align-items:center;gap:10px;margin-top:20px;">
                            <button type="submit" class="btn-save" id="btnProfile">
                                <i class="bi bi-check2"></i> Salvar alterações
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Card: Alterar Senha --}}
            <div class="profile-card">
                <div class="profile-card-header">
                    <i class="bi bi-lock" style="color:#3B82F6;"></i>
                    Alterar Senha
                </div>
                <div class="profile-card-body">
                    <form id="formPassword">
                        <div class="form-group">
                            <label>Senha atual</label>
                            <input type="password" class="form-control" id="currentPassword" placeholder="••••••••">
                            <div class="form-error d-none" id="errCurrentPwd"></div>
                        </div>
                        <div class="form-group">
                            <label>Nova senha</label>
                            <input type="password" class="form-control" id="newPassword" placeholder="Mínimo 8 caracteres">
                            <div class="form-error d-none" id="errNewPwd"></div>
                        </div>
                        <div class="form-group">
                            <label>Confirmar nova senha</label>
                            <input type="password" class="form-control" id="confirmPassword" placeholder="••••••••">
                        </div>
                        <button type="submit" class="btn-save" id="btnPassword" style="margin-top:4px;">
                            <i class="bi bi-shield-lock"></i> Alterar senha
                        </button>
                    </form>
                </div>
            </div>

        </div>

        {{-- ── Coluna direita: Avatar ── --}}
        <div>
            <div class="profile-card">
                <div class="profile-card-header">
                    <i class="bi bi-image" style="color:#3B82F6;"></i>
                    Foto de Perfil
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
                                Clique para enviar
                            </div>
                            <div class="upload-hint">JPG, PNG ou WebP · máx. 2MB</div>
                            <input type="file" id="avatarFile" accept="image/*">
                        </label>
                    </div>
                </div>
            </div>

            {{-- Info da conta --}}
            <div class="profile-card">
                <div class="profile-card-header">
                    <i class="bi bi-info-circle" style="color:#3B82F6;"></i>
                    Conta
                </div>
                <div class="profile-card-body" style="font-size:13px;color:#6b7280;">
                    <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #f7f8fa;">
                        <span>Papel</span>
                        <span style="font-weight:600;color:#1a1d23;">{{ ucfirst(auth()->user()->role) }}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #f7f8fa;">
                        <span>Empresa</span>
                        <span style="font-weight:600;color:#1a1d23;">{{ auth()->user()->tenant->name ?? '—' }}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;padding:6px 0;">
                        <span>Membro desde</span>
                        <span style="font-weight:600;color:#1a1d23;">{{ auth()->user()->created_at->format('d/m/Y') }}</span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
const profileUrl  = "{{ route('settings.profile.update') }}";
const passwordUrl = "{{ route('settings.profile.password') }}";
const avatarUrl   = "{{ route('settings.profile.avatar') }}";

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
            toastr.success(data.message ?? 'Perfil atualizado!');
        } else if (data.errors) {
            showErrors(data.errors, {name: 'errName', email: 'errEmail'});
        } else {
            toastr.error(data.message ?? 'Erro ao atualizar.');
        }
    } catch { toastr.error('Erro de conexão.'); }
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
            toastr.success(data.message ?? 'Senha alterada!');
            document.getElementById('formPassword').reset();
        } else if (data.errors) {
            showErrors(data.errors, {current_password: 'errCurrentPwd', password: 'errNewPwd'});
        } else {
            toastr.error(data.message ?? 'Erro ao alterar senha.');
        }
    } catch { toastr.error('Erro de conexão.'); }
    btn.disabled = false;
});

// ── Avatar ────────────────────────────────────────────────
document.getElementById('avatarFile').addEventListener('change', async function() {
    const file = this.files[0];
    if (!file) return;

    if (file.size > 2 * 1024 * 1024) {
        toastr.error('Arquivo muito grande. Máximo 2MB.');
        return;
    }

    const fd = new FormData();
    fd.append('avatar', file);
    fd.append('_token', document.querySelector('meta[name=csrf-token]').content);

    try {
        const res = await fetch(avatarUrl, { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (res.ok && data.success) {
            toastr.success(data.message ?? 'Foto atualizada!');
            // Atualiza preview
            const preview = document.getElementById('avatarPreview');
            preview.innerHTML = `<img src="${data.avatar_url}?t=${Date.now()}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;">`;
            // Atualiza avatares do layout
            document.querySelectorAll('.user-avatar').forEach(el => {
                el.style.background = 'transparent';
                el.innerHTML = `<img src="${data.avatar_url}?t=${Date.now()}" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;">`;
            });
        } else {
            toastr.error(data.message ?? 'Erro ao enviar imagem.');
        }
    } catch { toastr.error('Erro de conexão.'); }
});

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
</script>
@endpush
