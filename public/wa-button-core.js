/* Syncro WhatsApp Button — Core (injected by wa-button.js endpoint) */
/* CFG is defined by the wrapper: { token, phone, message, label, floating, apiBase } */

var VID_KEY='syncro_wa_'+CFG.token;
var vid=localStorage.getItem(VID_KEY);
if(!vid){vid='xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g,function(c){var r=Math.random()*16|0;return(c==='x'?r:r&0x3|0x8).toString(16)});localStorage.setItem(VID_KEY,vid)}

var P=new URLSearchParams(window.location.search);
var utm={utm_source:P.get('utm_source'),utm_medium:P.get('utm_medium'),utm_campaign:P.get('utm_campaign'),utm_content:P.get('utm_content'),utm_term:P.get('utm_term'),fbclid:P.get('fbclid'),gclid:P.get('gclid'),page_url:window.location.href,referrer_url:document.referrer,visitor_id:vid};

function trackAndOpen(){
    // Monta URL de redirect server-side com UTMs como query params
    var params=new URLSearchParams();
    if(utm.utm_source)params.set('utm_source',utm.utm_source);
    if(utm.utm_medium)params.set('utm_medium',utm.utm_medium);
    if(utm.utm_campaign)params.set('utm_campaign',utm.utm_campaign);
    if(utm.utm_content)params.set('utm_content',utm.utm_content);
    if(utm.utm_term)params.set('utm_term',utm.utm_term);
    if(utm.fbclid)params.set('fbclid',utm.fbclid);
    if(utm.gclid)params.set('gclid',utm.gclid);
    if(utm.page_url)params.set('page_url',utm.page_url);
    if(utm.referrer_url)params.set('referrer_url',utm.referrer_url);
    if(utm.visitor_id)params.set('visitor_id',utm.visitor_id);
    var qs=params.toString();
    var redirectUrl=CFG.apiBase+'/wa/'+CFG.token+(qs?'?'+qs:'');
    // Redirect via servidor — tracking 100% server-side, sem depender de sendBeacon
    window.location.href=redirectUrl;
}

/* ── CSS ─────────────────────────────────────────────── */
var style=document.createElement('style');
style.textContent='\
.syncro-wa-inline-btn{display:inline-flex;align-items:center;gap:10px;padding:14px 32px;border:none;border-radius:10px;cursor:pointer;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;font-size:15px;font-weight:600;color:#fff;text-decoration:none;position:relative;overflow:hidden;background:linear-gradient(-45deg,#61CE70,#25D366,#128C7E,#25D366,#61CE70);background-size:800% 400%;animation:syncro-wa-grad 5s infinite cubic-bezier(.62,.28,.23,.99) both;transition:all .5s;box-shadow:0 2px 8px rgba(97,206,112,0.3)}\
.syncro-wa-inline-btn:hover{box-shadow:0 4px 14px rgba(97,206,112,0.4)}\
.syncro-wa-inline-btn::before{content:"";background:#fff;width:100%;height:100%;position:absolute;top:0;left:0;border-radius:10px;transform:scaleX(0);transform-origin:left;transition:.5s ease}\
.syncro-wa-inline-btn:hover::before{transform:scaleX(1);transition:1s ease}\
.syncro-wa-inline-btn:hover span{color:#128C7E;transition:color .3s}\
.syncro-wa-inline-btn:hover svg{fill:#128C7E;transition:fill .3s}\
.syncro-wa-inline-btn::after{content:"";background:linear-gradient(10deg,rgba(209,210,234,0.3) 12.81%,transparent 66.66%);mix-blend-mode:overlay;width:90px;height:160%;position:absolute;transform:translateX(-50%) skew(-25deg);bottom:0;user-select:none;pointer-events:none;animation:syncro-wa-shine 6s infinite ease-in-out}\
@keyframes syncro-wa-grad{0%,100%{background-position:0% 50%}50%{background-position:100% 50%}}\
@keyframes syncro-wa-shine{0%,100%{left:-10%;opacity:0}20%{opacity:1}48%{left:140%;opacity:1}51%{opacity:0}}\
.syncro-wa-inline-btn svg{width:20px;height:20px;fill:#fff;flex-shrink:0;position:relative;z-index:2;transition:fill .3s}\
.syncro-wa-inline-btn span{position:relative;z-index:2;transition:color .3s}\
.syncro-wa-float-wrap{position:fixed !important;bottom:24px;right:24px;z-index:99998}\
.syncro-wa-float-btn{width:60px;height:60px;border-radius:50%;background:#25D366;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 16px rgba(37,211,102,0.4);transition:transform .2s,box-shadow .2s}\
.syncro-wa-float-btn:hover{transform:scale(1.08);box-shadow:0 6px 24px rgba(37,211,102,0.5)}\
.syncro-wa-float-btn svg{width:32px;height:32px;fill:#fff}\
@media(max-width:600px){.syncro-wa-float-wrap{bottom:16px;right:16px}.syncro-wa-float-btn{width:54px;height:54px}.syncro-wa-float-btn svg{width:28px;height:28px}}\
';
document.head.appendChild(style);

var SVG='<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>';

function renderButtons(){
    /* ── Render inline buttons ──────────────────────────── */
    var inlines=document.querySelectorAll('.syncro-wa-inline');
    for(var i=0;i<inlines.length;i++){
        if(inlines[i].querySelector('.syncro-wa-inline-btn')) continue;
        var a=document.createElement('a');
        a.className='syncro-wa-inline-btn syncro-wa-button';
        a.setAttribute('data-syncro-wa','inline');
        a.href='javascript:void(0)';
        a.onclick=function(e){e.preventDefault();trackAndOpen()};
        a.innerHTML=SVG+'<span>'+CFG.label+'</span>';
        inlines[i].appendChild(a);
    }

    /* ── Hijack direct /wa/ links on the page ─────────────── */
    var waLinks=document.querySelectorAll('a[href*="/wa/'+CFG.token+'"]');
    for(var j=0;j<waLinks.length;j++){
        (function(link){
            if(link.classList.contains('syncro-wa-button'))return;
            var origHref=link.getAttribute('href');
            var p=new URLSearchParams();
            if(utm.utm_source)p.set('utm_source',utm.utm_source);
            if(utm.utm_medium)p.set('utm_medium',utm.utm_medium);
            if(utm.utm_campaign)p.set('utm_campaign',utm.utm_campaign);
            if(utm.utm_content)p.set('utm_content',utm.utm_content);
            if(utm.utm_term)p.set('utm_term',utm.utm_term);
            if(utm.fbclid)p.set('fbclid',utm.fbclid);
            if(utm.gclid)p.set('gclid',utm.gclid);
            if(utm.page_url)p.set('page_url',utm.page_url);
            if(utm.referrer_url)p.set('referrer_url',utm.referrer_url);
            if(utm.visitor_id)p.set('visitor_id',utm.visitor_id);
            var qs=p.toString();
            if(qs)link.href=origHref+(origHref.indexOf('?')>-1?'&':'?')+qs;
        })(waLinks[j]);
    }

    /* ── Render floating button ─────────────────────────── */
    if(CFG.floating && !document.querySelector('.syncro-wa-float-wrap')){
        var wrap=document.createElement('div');
        wrap.className='syncro-wa-float-wrap';
        var btn=document.createElement('button');
        btn.className='syncro-wa-float-btn syncro-wa-button';
        btn.setAttribute('data-syncro-wa','float');
        btn.onclick=function(){trackAndOpen()};
        btn.innerHTML=SVG;
        wrap.appendChild(btn);
        document.body.appendChild(wrap);
    }
}

/* Run immediately if DOM ready, otherwise wait */
if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',renderButtons)}else{renderButtons()}
