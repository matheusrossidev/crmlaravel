@extends('master.layouts.app')
@php $title = 'Recursos para Parceiros'; $pageIcon = 'folder2-open'; @endphp

@section('topbar_actions')
<button class="m-btn m-btn-primary" onclick="openModal()"><i class="bi bi-plus-lg"></i> Novo Recurso</button>
@endsection

@section('content')
<div class="m-card">
    <div class="m-card-header">
        <div class="m-card-title"><i class="bi bi-folder2-open"></i> Recursos para Parceiros</div>
        <div style="font-size:12.5px;color:#9ca3af;">Materiais, guias e downloads disponíveis para parceiros.</div>
    </div>

    @if($resources->isEmpty())
        <div style="padding:60px;text-align:center;color:#9ca3af;">
            <i class="bi bi-folder2-open" style="font-size:36px;display:block;margin-bottom:10px;color:#d1d5db;"></i>
            <p style="font-size:14px;font-weight:600;color:#374151;">Nenhum recurso criado</p>
        </div>
    @else
        <div style="overflow-x:auto;">
            <table class="m-table">
                <thead><tr><th>Título</th><th>Categoria</th><th>Anexos</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @foreach($resources as $r)
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                @if($r->cover_image)
                                    <img src="{{ asset('storage/' . $r->cover_image) }}" style="width:40px;height:40px;border-radius:8px;object-fit:cover;">
                                @endif
                                <div>
                                    <div style="font-weight:700;color:#1a1d23;">{{ $r->title }}</div>
                                    @if($r->description) <div style="font-size:12px;color:#97A3B7;">{{ Str::limit($r->description, 50) }}</div> @endif
                                </div>
                            </div>
                        </td>
                        <td>{{ $r->category ?? '—' }}</td>
                        <td>{{ $r->attachments ? count($r->attachments) : 0 }} arquivo(s)</td>
                        <td>
                            @if($r->is_published) <span style="color:#059669;font-weight:600;font-size:12px;">Publicado</span>
                            @else <span style="color:#97A3B7;font-size:12px;">Rascunho</span> @endif
                        </td>
                        <td style="text-align:right;">
                            <button style="background:none;border:none;color:#0085f3;cursor:pointer;font-size:15px;padding:4px 6px;" onclick="editResource({{ $r->id }}, {!! htmlspecialchars(json_encode($r), ENT_QUOTES) !!})"><i class="bi bi-pencil"></i></button>
                            <button style="background:none;border:none;color:#ef4444;cursor:pointer;font-size:15px;padding:4px 6px;" onclick="deleteResource({{ $r->id }})"><i class="bi bi-trash3"></i></button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- Modal --}}
<div id="resModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center;overflow-y:auto;padding:20px;" onclick="if(event.target===this)closeModal()">
    <div style="background:#fff;border-radius:16px;padding:28px;width:100%;max-width:560px;box-shadow:0 20px 60px rgba(0,0,0,.2);margin:auto;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h3 id="modalTitle" style="margin:0;font-size:17px;font-weight:700;color:#111827;">Novo Recurso</h3>
            <button onclick="closeModal()" style="background:none;border:none;font-size:20px;color:#9ca3af;cursor:pointer;">&times;</button>
        </div>
        <input type="hidden" id="editId" value="">

        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:12.5px;font-weight:600;color:#374151;margin-bottom:6px;">Título *</label>
            <input type="text" id="fTitle" required style="width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;">
        </div>
        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:12.5px;font-weight:600;color:#374151;margin-bottom:6px;">Descrição</label>
            <textarea id="fDesc" rows="2" style="width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;font-family:inherit;resize:vertical;box-sizing:border-box;"></textarea>
        </div>
        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:12.5px;font-weight:600;color:#374151;margin-bottom:6px;">Conteúdo (HTML)</label>
            <textarea id="fContent" rows="5" style="width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;font-family:monospace;resize:vertical;box-sizing:border-box;"></textarea>
        </div>
        <div style="display:flex;gap:12px;margin-bottom:14px;">
            <div style="flex:1;">
                <label style="display:block;font-size:12.5px;font-weight:600;color:#374151;margin-bottom:6px;">Categoria</label>
                <input type="text" id="fCategory" placeholder="Ex: Marketing" style="width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;">
            </div>
            <div style="width:80px;">
                <label style="display:block;font-size:12.5px;font-weight:600;color:#374151;margin-bottom:6px;">Ordem</label>
                <input type="number" id="fOrder" min="0" value="0" style="width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;">
            </div>
        </div>
        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:12.5px;font-weight:600;color:#374151;margin-bottom:6px;">Capa (imagem)</label>
            <input type="file" id="fCover" accept="image/*" style="font-size:13px;">
        </div>
        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:12.5px;font-weight:600;color:#374151;margin-bottom:6px;">Anexos para download</label>
            <input type="file" id="fAttachments" multiple style="font-size:13px;">
            <div style="font-size:11px;color:#9ca3af;margin-top:4px;">Selecione um ou mais arquivos. PDFs, planilhas, apresentações etc.</div>
            <div id="existingAttachments" style="margin-top:8px;"></div>
        </div>
        <div style="margin-bottom:20px;display:flex;align-items:center;gap:10px;">
            <input type="checkbox" id="fPublished" style="width:16px;height:16px;accent-color:#0085f3;">
            <label for="fPublished" style="font-size:14px;color:#374151;cursor:pointer;">Publicado</label>
        </div>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button type="button" onclick="closeModal()" style="padding:9px 18px;background:#f3f4f6;border:1px solid #d1d5db;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">Cancelar</button>
            <button type="button" onclick="saveResource()" id="btnSave" style="padding:9px 18px;background:#0085f3;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;"><i class="bi bi-check-lg"></i> Salvar</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
function openModal(e){document.getElementById('resModal').style.display='flex';if(!e){document.getElementById('modalTitle').textContent='Novo Recurso';document.getElementById('editId').value='';document.getElementById('fTitle').value='';document.getElementById('fDesc').value='';document.getElementById('fContent').value='';document.getElementById('fCategory').value='';document.getElementById('fOrder').value='0';document.getElementById('fPublished').checked=false;document.getElementById('fCover').value='';document.getElementById('fAttachments').value='';document.getElementById('existingAttachments').innerHTML='';}}
function closeModal(){document.getElementById('resModal').style.display='none';}
function editResource(id,d){document.getElementById('editId').value=id;document.getElementById('modalTitle').textContent='Editar Recurso';document.getElementById('fTitle').value=d.title;document.getElementById('fDesc').value=d.description||'';document.getElementById('fContent').value=d.content||'';document.getElementById('fCategory').value=d.category||'';document.getElementById('fOrder').value=d.sort_order||0;document.getElementById('fPublished').checked=!!d.is_published;
    // Show existing attachments
    const ea=document.getElementById('existingAttachments');ea.innerHTML='';
    if(d.attachments&&d.attachments.length){d.attachments.forEach(a=>{ea.innerHTML+=`<div style="display:flex;align-items:center;gap:6px;padding:4px 0;font-size:12px;color:#374151;"><i class="bi bi-paperclip" style="color:#0085f3;"></i> ${a.name||'Arquivo'}</div>`;});}
    openModal(true);}
async function saveResource(){const id=document.getElementById('editId').value;const fd=new FormData();fd.append('title',document.getElementById('fTitle').value);fd.append('description',document.getElementById('fDesc').value);fd.append('content',document.getElementById('fContent').value);fd.append('category',document.getElementById('fCategory').value);fd.append('sort_order',document.getElementById('fOrder').value);fd.append('is_published',document.getElementById('fPublished').checked?'1':'0');
    const cover=document.getElementById('fCover').files[0];if(cover)fd.append('cover',cover);
    const atts=document.getElementById('fAttachments').files;for(let i=0;i<atts.length;i++)fd.append('new_attachments[]',atts[i]);
    if(id)fd.append('_method','PUT');
    const url=id?'{{ route("master.partner-resources.update","__ID__") }}'.replace('__ID__',id):'{{ route("master.partner-resources.store") }}';
    document.getElementById('btnSave').disabled=true;
    try{const r=await fetch(url,{method:'POST',headers:{'X-CSRF-TOKEN':CSRF,Accept:'application/json'},body:fd});const d=await r.json();if(d.success){toastr.success('Salvo!');setTimeout(()=>location.reload(),600);}else toastr.error(d.message||'Erro');}catch{toastr.error('Erro');}
    document.getElementById('btnSave').disabled=false;}
function deleteResource(id){if(!confirm('Excluir?'))return;fetch('{{ route("master.partner-resources.destroy","__ID__") }}'.replace('__ID__',id),{method:'DELETE',headers:{'X-CSRF-TOKEN':CSRF,Accept:'application/json'}}).then(r=>r.json()).then(d=>{if(d.success)location.reload();});}
</script>
@endpush
