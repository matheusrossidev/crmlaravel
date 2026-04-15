{{--
  Body dos backup codes. Reusado por master e tenant profile.
  Vars: $codes, $justEnabled, $routePrefix
--}}
<div style="max-width:520px;margin:0 auto;">

    <div class="m-card">
        <div class="m-card-header">
            <div class="m-card-title">
                <i class="bi bi-key"></i>
                Códigos de Backup
            </div>
            <a href="{{ route(($routePrefix ?? 'master.2fa') . '.setup') }}" class="m-btn m-btn-ghost m-btn-sm" style="text-decoration:none;">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>

        <div class="m-card-body">
            @if($justEnabled)
                <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:14px 18px;margin-bottom:20px;font-size:13px;color:#1e40af;display:flex;align-items:flex-start;gap:10px;">
                    <i class="bi bi-info-circle-fill" style="margin-top:2px;"></i>
                    <div>
                        <strong>2FA ativado com sucesso!</strong><br>
                        Salve estes códigos de backup em um lugar seguro. Cada código funciona uma única vez, caso perca acesso ao app autenticador.
                    </div>
                </div>
            @endif

            @if($codes)
                <div style="background:#f8fafc;border:2px solid #e8eaf0;border-radius:14px;padding:20px;">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px;">
                        @foreach($codes as $code)
                            <div style="background:#fff;border:1px solid #e8eaf0;border-radius:8px;padding:10px;text-align:center;font-family:'Courier New',monospace;font-size:15px;font-weight:700;color:#1a1d23;letter-spacing:1px;">
                                {{ $code }}
                            </div>
                        @endforeach
                    </div>
                    <button onclick="copyCodes()" id="btnCopy" style="width:100%;padding:10px;background:#eff6ff;color:#0085f3;border:1.5px solid #bfdbfe;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;">
                        <i class="bi bi-clipboard"></i> Copiar todos os códigos
                    </button>
                </div>
                <div style="margin-top:16px;padding:12px 16px;background:#fef3c7;border:1px solid #fde68a;border-radius:10px;font-size:12.5px;color:#92400e;display:flex;align-items:flex-start;gap:8px;">
                    <i class="bi bi-exclamation-triangle-fill" style="margin-top:2px;"></i>
                    <div>Cada código funciona <strong>uma única vez</strong>. Guarde-os em local seguro. Depois que sair desta página, os códigos não serão mostrados novamente.</div>
                </div>
            @else
                <p style="font-size:14px;color:#6b7280;margin:0 0 20px;">
                    Os códigos de backup não podem ser mostrados novamente. Se precisar de novos códigos, regenere abaixo.
                </p>
            @endif

            <div style="margin-top:24px;padding-top:20px;border-top:1px solid #f0f2f7;">
                <h6 style="font-size:13px;font-weight:700;color:#1a1d23;margin:0 0 8px;">Regenerar Códigos</h6>
                <p style="font-size:12.5px;color:#6b7280;margin:0 0 12px;">Gera 8 novos códigos e invalida os anteriores. Digite sua senha para confirmar.</p>

                @if($errors->any())
                    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:8px 12px;font-size:13px;color:#dc2626;margin-bottom:12px;">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route(($routePrefix ?? 'master.2fa') . '.regenerate-codes') }}" style="display:flex;gap:10px;align-items:flex-end;">
                    @csrf
                    <input type="password" name="password" placeholder="Sua senha" required
                           style="flex:1;padding:9px 14px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;">
                    <button type="submit" class="m-btn m-btn-ghost m-btn-sm">
                        <i class="bi bi-arrow-repeat"></i> Regenerar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function copyCodes() {
    const codes = @json($codes ?? []);
    if (!codes.length) return;
    const text = codes.join('\n');
    navigator.clipboard.writeText(text).then(() => {
        const btn = document.getElementById('btnCopy');
        btn.innerHTML = '<i class="bi bi-check-lg"></i> Copiado!';
        btn.style.background = '#d1fae5';
        btn.style.color = '#065f46';
        btn.style.borderColor = '#86efac';
        setTimeout(() => {
            btn.innerHTML = '<i class="bi bi-clipboard"></i> Copiar todos os códigos';
            btn.style.background = '#eff6ff';
            btn.style.color = '#0085f3';
            btn.style.borderColor = '#bfdbfe';
        }, 2000);
    });
}
</script>
@endpush
