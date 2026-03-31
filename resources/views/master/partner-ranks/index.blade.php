@extends('master.layouts.app')
@php $title = 'Ranks de Parceiros'; $pageIcon = 'award'; @endphp

@section('topbar_actions')
<button class="m-btn m-btn-primary" onclick="openModal()"><i class="bi bi-plus-lg"></i> Novo Rank</button>
@endsection

@section('content')
<div class="m-card">
    <div class="m-card-header">
        <div class="m-card-title"><i class="bi bi-award"></i> Ranks de Parceiros</div>
        <div style="font-size:12.5px;color:#9ca3af;">Defina os níveis de parceiro com nome, imagem, quantidade mínima de vendas e % de comissão.</div>
    </div>

    @if($ranks->isEmpty())
        <div style="padding:60px;text-align:center;color:#9ca3af;">
            <i class="bi bi-award" style="font-size:36px;display:block;margin-bottom:10px;color:#d1d5db;"></i>
            <p style="font-size:14px;font-weight:600;color:#374151;">Nenhum rank criado</p>
        </div>
    @else
        <div style="overflow-x:auto;">
            <table class="m-table">
                <thead><tr><th>Ordem</th><th>Imagem</th><th>Nome</th><th>Vendas mín.</th><th>Comissão</th><th>Cor</th><th></th></tr></thead>
                <tbody>
                    @foreach($ranks as $rank)
                    <tr>
                        <td style="font-weight:600;color:#97A3B7;">{{ $rank->sort_order }}</td>
                        <td>
                            @if($rank->image_path)
                                <img src="{{ asset('storage/' . $rank->image_path) }}" style="width:36px;height:36px;border-radius:8px;object-fit:cover;">
                            @else
                                <div style="width:36px;height:36px;border-radius:8px;background:{{ $rank->color }}20;display:flex;align-items:center;justify-content:center;"><i class="bi bi-trophy-fill" style="color:{{ $rank->color }};font-size:16px;"></i></div>
                            @endif
                        </td>
                        <td><span style="font-weight:700;color:#1a1d23;">{{ $rank->name }}</span></td>
                        <td>{{ $rank->min_sales }} vendas</td>
                        <td><span style="font-weight:700;color:#059669;">{{ number_format($rank->commission_pct, 1) }}%</span></td>
                        <td><div style="width:20px;height:20px;border-radius:4px;background:{{ $rank->color }};"></div></td>
                        <td style="text-align:right;">
                            <button style="background:none;border:none;color:#0085f3;cursor:pointer;font-size:15px;padding:4px 6px;" onclick="editRank({{ $rank->id }}, {{ json_encode($rank) }})" title="Editar"><i class="bi bi-pencil"></i></button>
                            <button style="background:none;border:none;color:#ef4444;cursor:pointer;font-size:15px;padding:4px 6px;" onclick="deleteRank({{ $rank->id }})" title="Excluir"><i class="bi bi-trash3"></i></button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- Modal --}}
<div id="rankModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center;" onclick="if(event.target===this)closeModal()">
    <div style="background:#fff;border-radius:16px;padding:28px;width:100%;max-width:480px;box-shadow:0 20px 60px rgba(0,0,0,.2);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h3 id="modalTitle" style="margin:0;font-size:17px;font-weight:700;color:#111827;">Novo Rank</h3>
            <button onclick="closeModal()" style="background:none;border:none;font-size:20px;color:#9ca3af;cursor:pointer;">&times;</button>
        </div>
        <form id="rankForm" enctype="multipart/form-data">
            <input type="hidden" id="editId" value="">
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:12.5px;font-weight:600;color:#374151;margin-bottom:6px;">Nome *</label>
                <input type="text" id="fName" placeholder="Ex: Bronze, Prata, Ouro" required style="width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;">
            </div>
            <div style="display:flex;gap:12px;margin-bottom:14px;">
                <div style="flex:1;">
                    <label style="display:block;font-size:12.5px;font-weight:600;color:#374151;margin-bottom:6px;">Vendas mínimas *</label>
                    <input type="number" id="fMinSales" min="0" placeholder="0" required style="width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;">
                </div>
                <div style="flex:1;">
                    <label style="display:block;font-size:12.5px;font-weight:600;color:#374151;margin-bottom:6px;">Comissão (%) *</label>
                    <input type="number" id="fPct" min="0" max="100" step="0.5" placeholder="5.0" required style="width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;">
                </div>
            </div>
            <div style="display:flex;gap:12px;margin-bottom:14px;">
                <div style="flex:1;">
                    <label style="display:block;font-size:12.5px;font-weight:600;color:#374151;margin-bottom:6px;">Cor</label>
                    <input type="color" id="fColor" value="#6b7280" style="width:100%;height:38px;padding:2px;border:1px solid #d1d5db;border-radius:8px;cursor:pointer;">
                </div>
                <div style="flex:1;">
                    <label style="display:block;font-size:12.5px;font-weight:600;color:#374151;margin-bottom:6px;">Ordem</label>
                    <input type="number" id="fOrder" min="0" value="0" style="width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;">
                </div>
            </div>
            <div style="margin-bottom:20px;">
                <label style="display:block;font-size:12.5px;font-weight:600;color:#374151;margin-bottom:6px;">Imagem (opcional)</label>
                <input type="file" id="fImage" accept="image/*" style="font-size:13px;">
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" onclick="closeModal()" style="padding:9px 18px;background:#f3f4f6;border:1px solid #d1d5db;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">Cancelar</button>
                <button type="button" onclick="saveRank()" id="btnSave" style="padding:9px 18px;background:#0085f3;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;"><i class="bi bi-check-lg"></i> Salvar</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
function openModal(e){document.getElementById('rankModal').style.display='flex';if(!e){document.getElementById('modalTitle').textContent='Novo Rank';document.getElementById('editId').value='';document.getElementById('fName').value='';document.getElementById('fMinSales').value='';document.getElementById('fPct').value='';document.getElementById('fColor').value='#6b7280';document.getElementById('fOrder').value='0';document.getElementById('fImage').value='';}}
function closeModal(){document.getElementById('rankModal').style.display='none';}
function editRank(id,d){document.getElementById('editId').value=id;document.getElementById('modalTitle').textContent='Editar Rank';document.getElementById('fName').value=d.name;document.getElementById('fMinSales').value=d.min_sales;document.getElementById('fPct').value=d.commission_pct;document.getElementById('fColor').value=d.color||'#6b7280';document.getElementById('fOrder').value=d.sort_order||0;openModal(true);}
async function saveRank(){const id=document.getElementById('editId').value;const fd=new FormData();fd.append('name',document.getElementById('fName').value);fd.append('min_sales',document.getElementById('fMinSales').value);fd.append('commission_pct',document.getElementById('fPct').value);fd.append('color',document.getElementById('fColor').value);fd.append('sort_order',document.getElementById('fOrder').value);const f=document.getElementById('fImage').files[0];if(f)fd.append('image',f);if(id)fd.append('_method','PUT');const url=id?'{{ route("master.partner-ranks.update","__ID__") }}'.replace('__ID__',id):'{{ route("master.partner-ranks.store") }}';document.getElementById('btnSave').disabled=true;try{const r=await fetch(url,{method:'POST',headers:{'X-CSRF-TOKEN':CSRF,Accept:'application/json'},body:fd});const d=await r.json();if(d.success){toastr.success('Salvo!');setTimeout(()=>location.reload(),600);}else toastr.error(d.message||'Erro');}catch{toastr.error('Erro');}document.getElementById('btnSave').disabled=false;}
function deleteRank(id){if(!confirm('Excluir?'))return;fetch('{{ route("master.partner-ranks.destroy","__ID__") }}'.replace('__ID__',id),{method:'DELETE',headers:{'X-CSRF-TOKEN':CSRF,Accept:'application/json'}}).then(r=>r.json()).then(d=>{if(d.success)location.reload();});}
</script>
@endpush
