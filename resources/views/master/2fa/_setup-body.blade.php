{{--
  Body do setup de 2FA. Reusado por:
   - master/2fa/setup.blade.php (layout master)
   - tenant/profile/2fa-setup.blade.php (layout tenant)
  Vars: $enabled, $qrImage, $secret, $routePrefix
--}}
<div style="max-width:600px;margin:0 auto;">

    @if(session('success'))
        <div style="background:#d1fae5;border:1px solid #86efac;border-radius:12px;padding:14px 18px;margin-bottom:20px;font-size:13px;color:#065f46;display:flex;align-items:center;gap:10px;">
            <i class="bi bi-check-circle-fill"></i>
            {{ session('success') }}
        </div>
    @endif

    <div class="m-card">
        <div class="m-card-header">
            <div class="m-card-title">
                <i class="bi bi-shield-lock"></i>
                Autenticação em Dois Fatores (2FA)
            </div>
            @if($enabled)
                <span style="display:inline-flex;align-items:center;gap:6px;background:#d1fae5;color:#065f46;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;">
                    <i class="bi bi-check-circle-fill"></i> Ativado
                </span>
            @else
                <span style="display:inline-flex;align-items:center;gap:6px;background:#f3f4f6;color:#6b7280;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;">
                    <i class="bi bi-x-circle"></i> Desativado
                </span>
            @endif
        </div>

        <div class="m-card-body">
            @if($enabled)
                <p style="font-size:14px;color:#374151;line-height:1.6;margin:0 0 20px;">
                    A autenticação em dois fatores está ativa na sua conta. Toda vez que fizer login, será necessário digitar o código do app autenticador.
                </p>

                <div style="display:flex;gap:12px;flex-wrap:wrap;">
                    <a href="{{ route(($routePrefix ?? 'master.2fa') . '.backup-codes') }}" class="m-btn m-btn-ghost" style="text-decoration:none;">
                        <i class="bi bi-key"></i> Ver/Regenerar Códigos de Backup
                    </a>
                    <button onclick="showDisableModal()" class="m-btn m-btn-danger">
                        <i class="bi bi-shield-x"></i> Desativar 2FA
                    </button>
                </div>

            @else
                <div style="margin-bottom:24px;">
                    <p style="font-size:14px;color:#374151;line-height:1.6;margin:0 0 16px;">
                        Para ativar, escaneie o QR Code abaixo com seu app autenticador (Google Authenticator, Authy, 1Password, etc.) e depois digite o código de 6 dígitos para confirmar.
                    </p>
                </div>

                <div style="display:flex;gap:28px;align-items:flex-start;flex-wrap:wrap;">
                    <div style="flex-shrink:0;text-align:center;">
                        <div style="background:#f8fafc;border:2px solid #e8eaf0;border-radius:16px;padding:16px;display:inline-block;">
                            <img src="{{ $qrImage }}" alt="QR Code" style="width:200px;height:200px;">
                        </div>
                        <div style="margin-top:10px;">
                            <button onclick="toggleManualKey()" style="background:none;border:none;color:#0085f3;font-size:12px;font-weight:600;cursor:pointer;">
                                <i class="bi bi-keyboard"></i> Digitar manualmente
                            </button>
                        </div>
                        <div id="manualKey" style="display:none;margin-top:8px;background:#f8fafc;border:1px solid #e8eaf0;border-radius:8px;padding:8px 12px;">
                            <div style="font-size:11px;color:#6b7280;margin-bottom:4px;">Chave manual:</div>
                            <code style="font-size:13px;font-weight:700;color:#1a1d23;letter-spacing:2px;word-break:break-all;">{{ $secret }}</code>
                        </div>
                    </div>

                    <div style="flex:1;min-width:220px;">
                        <div style="background:#eff6ff;border-radius:12px;padding:16px;margin-bottom:20px;">
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                                <i class="bi bi-info-circle" style="color:#0085f3;"></i>
                                <strong style="font-size:13px;color:#1a1d23;">Instruções</strong>
                            </div>
                            <ol style="margin:0;padding-left:18px;font-size:12.5px;color:#374151;line-height:1.7;">
                                <li>Abra o app autenticador</li>
                                <li>Toque em <strong>+</strong> e escaneie o QR</li>
                                <li>Digite o código de 6 dígitos abaixo</li>
                            </ol>
                        </div>

                        @if($errors->any())
                            <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:10px 14px;font-size:13px;color:#dc2626;margin-bottom:14px;display:flex;align-items:center;gap:8px;">
                                <i class="bi bi-exclamation-circle"></i>
                                {{ $errors->first() }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route(($routePrefix ?? 'master.2fa') . '.confirm') }}">
                            @csrf
                            <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">
                                Código de verificação
                            </label>
                            <input type="text" name="code" placeholder="000000" maxlength="6" inputmode="numeric" autocomplete="one-time-code" autofocus required
                                   style="width:100%;padding:12px 16px;border:2px solid #e8eaf0;border-radius:10px;font-size:20px;font-weight:700;text-align:center;letter-spacing:6px;color:#1a1d23;outline:none;">
                            <button type="submit" class="m-btn m-btn-primary" style="width:100%;margin-top:12px;justify-content:center;padding:12px;">
                                <i class="bi bi-shield-check"></i> Ativar 2FA
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@if($enabled)
<div id="disableModal" style="display:none;position:fixed;inset:0;z-index:10000;background:rgba(15,23,42,.5);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;width:100%;max-width:400px;margin:16px;padding:28px;box-shadow:0 24px 64px rgba(0,0,0,.18);">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:18px;">
            <div style="width:44px;height:44px;border-radius:12px;background:#FEF2F2;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="bi bi-shield-x" style="color:#EF4444;font-size:20px;"></i>
            </div>
            <div>
                <h5 style="font-size:16px;font-weight:700;color:#111827;margin:0 0 4px;">Desativar 2FA</h5>
                <p style="font-size:13px;color:#6b7280;margin:0;">Digite sua senha para confirmar.</p>
            </div>
        </div>
        <form method="POST" action="{{ route(($routePrefix ?? 'master.2fa') . '.disable') }}">
            @csrf
            <input type="password" name="password" placeholder="Sua senha" required
                   style="width:100%;padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:14px;outline:none;margin-bottom:16px;">
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" onclick="closeDisableModal()" class="m-btn m-btn-ghost m-btn-sm">Cancelar</button>
                <button type="submit" class="m-btn m-btn-danger m-btn-sm">Desativar</button>
            </div>
        </form>
    </div>
</div>
@endif

@push('scripts')
<script>
function toggleManualKey() {
    const el = document.getElementById('manualKey');
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
function showDisableModal() { document.getElementById('disableModal').style.display = 'flex'; }
function closeDisableModal() { document.getElementById('disableModal').style.display = 'none'; }
</script>
@endpush
