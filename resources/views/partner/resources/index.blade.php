@extends('tenant.layouts.app')
@php $title = 'Recursos'; $pageIcon = 'folder2-open'; @endphp

@push('styles')
<style>
.res-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; }
.res-card { background: #fff; border: 1.5px solid #e8eaf0; border-radius: 14px; overflow: hidden; transition: box-shadow .15s, transform .15s; cursor: pointer; text-decoration: none; color: inherit; display: block; }
.res-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.06); transform: translateY(-2px); }
.res-cover { width: 100%; height: 160px; object-fit: cover; background: #f3f4f6; }
.res-cover-placeholder { width: 100%; height: 160px; background: #f8fafc; display: flex; align-items: center; justify-content: center; color: #d1d5db; font-size: 40px; }
.res-body { padding: 16px 18px; }
.res-cat { font-size: 11px; font-weight: 600; color: #0085f3; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 6px; }
.res-title { font-size: 15px; font-weight: 700; color: #1a1d23; margin-bottom: 6px; }
.res-desc { font-size: 13px; color: #6b7280; line-height: 1.5; }
.res-filter { display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; }
.res-filter-btn { padding: 6px 14px; border-radius: 99px; font-size: 12px; font-weight: 600; border: 1.5px solid #e5e7eb; background: #fff; color: #374151; cursor: pointer; transition: all .12s; }
.res-filter-btn.active, .res-filter-btn:hover { background: #0085f3; color: #fff; border-color: #0085f3; }
</style>
@endpush

@section('content')
<div class="page-container">
    <div style="margin-bottom:24px;">
        <div style="font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:#97A3B7;margin-bottom:4px;">PORTAL DO PARCEIRO</div>
        <h1 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:22px;font-weight:700;color:#1a1d23;margin:0 0 4px;">Recursos</h1>
        <p style="font-size:13.5px;color:#677489;margin:0;">Materiais, guias e downloads para impulsionar suas vendas.</p>
    </div>

    @if($categories->isNotEmpty())
        <div class="res-filter">
            <button class="res-filter-btn active" onclick="filterRes('all', this)">Todos</button>
            @foreach($categories as $cat)
                <button class="res-filter-btn" onclick="filterRes('{{ Str::slug($cat) }}', this)">{{ $cat }}</button>
            @endforeach
        </div>
    @endif

    @if($resources->isEmpty())
        <div style="padding:60px;text-align:center;background:#fff;border:1.5px solid #e8eaf0;border-radius:14px;color:#97A3B7;">
            <i class="bi bi-folder2-open" style="font-size:40px;display:block;margin-bottom:10px;color:#d1d5db;"></i>
            <p style="font-size:14px;font-weight:600;color:#374151;">Nenhum recurso disponível</p>
            <p style="font-size:13px;">Novos materiais serão publicados em breve.</p>
        </div>
    @else
        <div class="res-grid" id="resGrid">
            @foreach($resources as $r)
                <a href="{{ route('partner.resources.show', $r->slug) }}" class="res-card" data-cat="{{ Str::slug($r->category ?? '') }}">
                    @if($r->cover_image)
                        <img src="{{ asset('storage/' . $r->cover_image) }}" class="res-cover" alt="{{ $r->title }}">
                    @else
                        <div class="res-cover-placeholder"><i class="bi bi-file-earmark-richtext"></i></div>
                    @endif
                    <div class="res-body">
                        @if($r->category) <div class="res-cat">{{ $r->category }}</div> @endif
                        <div class="res-title">{{ $r->title }}</div>
                        @if($r->description) <div class="res-desc">{{ Str::limit($r->description, 100) }}</div> @endif
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
function filterRes(cat, btn) {
    document.querySelectorAll('.res-filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.res-card').forEach(c => {
        c.style.display = (cat === 'all' || c.dataset.cat === cat) ? '' : 'none';
    });
}
</script>
@endpush
