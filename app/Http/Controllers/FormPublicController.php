<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Form;
use App\Services\Forms\FormSubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class FormPublicController extends Controller
{
    public function show(string $slug): View
    {
        $form = Form::withoutGlobalScope('tenant')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        if (! $form->isAcceptingSubmissions()) {
            return view('forms.closed', compact('form'));
        }

        // Track hosted view
        Form::withoutGlobalScope('tenant')
            ->where('id', $form->id)
            ->increment('views_count');
        Form::withoutGlobalScope('tenant')
            ->where('id', $form->id)
            ->increment('views_count_hosted');

        $viewMap = [
            'conversational' => 'forms.public-conversational',
            'multistep'      => 'forms.public-multistep',
        ];

        $view = $viewMap[$form->type] ?? 'forms.public';

        return view($view, compact('form'));
    }

    public function submit(Request $request, string $slug): JsonResponse|View|\Illuminate\Http\RedirectResponse
    {
        $form = Form::withoutGlobalScope('tenant')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $embedMode   = $request->input('_embed_mode', 'hosted');
        $referrerUrl = $request->input('_referrer') ?: $request->header('referer');

        try {
            $service = app(FormSubmissionService::class);
            $service->process(
                $form,
                $request->except(['_token', '_website_url', '_embed_mode', '_referrer']),
                $request->ip(),
                $request->userAgent(),
                $embedMode,
                $referrerUrl,
            );

            if ($request->expectsJson()) {
                return $this->corsJson([
                    'success'            => true,
                    'confirmation_type'  => $form->confirmation_type,
                    'confirmation_value' => $form->confirmation_value ?? __('forms.default_thanks'),
                ]);
            }

            if ($form->confirmation_type === 'redirect' && $form->confirmation_value) {
                return redirect()->away($form->confirmation_value);
            }

            return view('forms.thanks', [
                'form'    => $form,
                'message' => $form->confirmation_value ?? __('forms.default_thanks'),
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson()) {
                return $this->corsJson(['success' => false, 'errors' => $e->errors()], 422);
            }
            return back()->withErrors($e->errors())->withInput();

        } catch (\RuntimeException $e) {
            if ($request->expectsJson()) {
                return $this->corsJson(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Return form configuration as JSON for the native SDK.
     */
    public function config(string $slug): JsonResponse
    {
        $form = Form::withoutGlobalScope('tenant')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (! $form || ! $form->isAcceptingSubmissions()) {
            return $this->corsJson(['error' => 'not_available'], 404);
        }

        return $this->corsJson([
            'id'                => $form->id,
            'name'              => $form->name,
            'type'              => $form->type,
            'fields'            => $form->fields ?? [],
            'conditional_logic' => $form->conditional_logic ?? [],
            'confirmation_type' => $form->confirmation_type,
            'confirmation_value' => $form->confirmation_value ?? __('forms.default_thanks'),
            'styling' => [
                'brand_color'        => $form->brand_color ?? '#0085f3',
                'button_color'       => $form->button_color ?? '#0085f3',
                'button_text_color'  => $form->button_text_color ?? '#ffffff',
                'label_color'        => $form->label_color ?? '#374151',
                'input_border_color' => $form->input_border_color ?? '#e5e7eb',
                'input_bg_color'     => $form->input_bg_color ?? '#ffffff',
                'input_text_color'   => $form->input_text_color ?? '#1a1d23',
                'card_color'         => $form->card_color ?? '#ffffff',
                'font_family'        => $form->font_family ?? 'Inter',
                'border_radius'      => (int) ($form->border_radius ?? 8),
            ],
            'widget' => [
                'trigger'    => $form->widget_trigger ?? 'immediate',
                'delay'      => (int) ($form->widget_delay ?? 5),
                'scroll_pct' => (int) ($form->widget_scroll_pct ?? 50),
                'show_once'  => (bool) ($form->widget_show_once ?? true),
                'position'   => $form->widget_position ?? 'center',
            ],
            'submit_url' => url('/api/form/' . $form->slug . '/submit'),
            'labels' => [
                'submit' => __('forms.submit_button'),
                'required_error' => __('forms.default_thanks'),
            ],
        ]);
    }

    /**
     * Track a view for inline/popup embed modes.
     */
    public function trackView(Request $request, string $slug): JsonResponse
    {
        $mode = $request->input('mode', 'inline');
        if (! in_array($mode, ['inline', 'popup', 'hosted'], true)) {
            $mode = 'inline';
        }

        $form = Form::withoutGlobalScope('tenant')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (! $form) {
            return $this->corsJson(['success' => false], 404);
        }

        $column = 'views_count_' . $mode;
        Form::withoutGlobalScope('tenant')
            ->where('id', $form->id)
            ->increment($column);
        Form::withoutGlobalScope('tenant')
            ->where('id', $form->id)
            ->increment('views_count');

        return $this->corsJson(['success' => true]);
    }

    /**
     * CORS preflight handler.
     */
    public function cors(): Response
    {
        return response('', 204)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Accept, X-Requested-With')
            ->header('Access-Control-Max-Age', '86400');
    }

    /**
     * Serve native JS SDK for external sites.
     * Replaces the old iframe-based embed.
     */
    public function script(string $slug): Response
    {
        $form = Form::withoutGlobalScope('tenant')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (! $form) {
            return response('/* Form not found */', 404)
                ->header('Content-Type', 'application/javascript')
                ->header('Access-Control-Allow-Origin', '*');
        }

        $base      = rtrim(config('app.url'), '/');
        $configUrl = $base . '/api/form/' . $form->slug . '/config.json';
        $trackUrl  = $base . '/api/form/' . $form->slug . '/track-view';
        $formId    = $form->id;

        $js = $this->buildSdkJs($configUrl, $trackUrl, $formId, $form->slug);

        return response($js, 200)
            ->header('Content-Type', 'application/javascript; charset=utf-8')
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Cache-Control', 'public, max-age=300');
    }

    private function corsJson(array $data, int $status = 200): JsonResponse
    {
        return response()->json($data, $status, [
            'Access-Control-Allow-Origin'  => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Accept, X-Requested-With',
        ]);
    }

    private function buildSdkJs(string $configUrl, string $trackUrl, int $formId, string $slug): string
    {
        // SDK self-contained — renders form natively into host DOM, no iframe, no chrome.
        //
        // IMPORTANTE: com async=true o `document.currentScript` pode ser null em alguns browsers
        // e o fallback `scripts[last]` pegava script alheio (GTM, Hotjar) resultando em form
        // inserido em lugar errado ou invisível. Solução: identificamos o script pelo form_id
        // (imutável — slug pode mudar se user renomear o form).
        return <<<JS
(function() {
    'use strict';
    var FORM_ID    = {$formId};
    var SLUG       = '{$slug}';
    var CONFIG_URL = '{$configUrl}';
    var TRACK_URL  = '{$trackUrl}';
    var ROOT_ID    = 'syncro-form-' + FORM_ID;

    // Resolução da tag script em ordem de confiabilidade:
    // 1) currentScript quando disponível (raro ser null mesmo com async em browsers modernos)
    // 2) Script com data-form-id igual ao nosso ID (forma robusta — ID é imutável)
    // 3) Legacy data-form="slug" (embeds antigos antes da mudança pra ID)
    // 4) Fallback: último script com src contendo o slug atual
    var SCRIPT = document.currentScript
        || document.querySelector('script[data-form-id="' + FORM_ID + '"]')
        || document.querySelector('script[data-form="' + SLUG + '"]')
        || (function(){
            var all = document.getElementsByTagName('script');
            for (var i = all.length - 1; i >= 0; i--) {
                if (all[i].src && all[i].src.indexOf('/api/form/' + SLUG + '.js') !== -1) return all[i];
            }
            return null;
        })();

    if (!SCRIPT) {
        console.warn('[Syncro Form] ID ' + FORM_ID + ' não conseguiu localizar a tag script. Adicione data-form-id="' + FORM_ID + '" na tag.');
        return;
    }

    var ds = SCRIPT.dataset || {};
    var mode       = (ds.mode || 'inline').toLowerCase();
    var trigger    = (ds.trigger || '').toLowerCase();
    var dataDelay  = ds.delay ? parseInt(ds.delay, 10) : null;
    var dataScroll = ds.scroll ? parseInt(ds.scroll, 10) : null;
    var dataShowOnce = ds.showOnce ? ds.showOnce === 'true' : null;
    var dataPos    = (ds.position || '').toLowerCase();

    fetch(CONFIG_URL, { method: 'GET' }).then(function(r){ return r.json(); }).then(function(cfg){
        if (!cfg || cfg.error) return;
        var w = cfg.widget || {};
        var T = trigger || w.trigger || 'immediate';
        var D = dataDelay !== null ? dataDelay : w.delay;
        var SP = dataScroll !== null ? dataScroll : w.scroll_pct;
        var SO = dataShowOnce !== null ? dataShowOnce : w.show_once;
        var POS = dataPos || w.position || 'center';

        injectStyles(cfg.styling, POS);

        if (mode === 'popup') {
            if (SO && localStorage.getItem('syncro_form_shown_' + FORM_ID)) return;
            schedulePopup(T, D, SP, function() {
                showPopup(cfg, POS, SO);
            });
        } else {
            // inline: render immediately in place of the script
            var container = document.createElement('div');
            container.id = ROOT_ID;
            container.className = 'syncro-form-container';
            SCRIPT.parentNode.insertBefore(container, SCRIPT.nextSibling);
            renderForm(cfg, container, 'inline');
            trackView('inline');
        }
    }).catch(function(){});

    // ── Trigger scheduling ──────────────────────────────────
    function schedulePopup(trigger, delay, scrollPct, showFn) {
        if (trigger === 'immediate') return showFn();
        if (trigger === 'time') return setTimeout(showFn, (delay || 0) * 1000);
        if (trigger === 'scroll') {
            var fired = false;
            var onScroll = function() {
                if (fired) return;
                var h = document.documentElement.scrollHeight - window.innerHeight;
                var pct = h > 0 ? (window.scrollY / h) * 100 : 100;
                if (pct >= (scrollPct || 50)) { fired = true; window.removeEventListener('scroll', onScroll); showFn(); }
            };
            window.addEventListener('scroll', onScroll, { passive: true });
            return;
        }
        if (trigger === 'exit') {
            var firedE = false;
            var onLeave = function(e) {
                if (firedE) return;
                if (e.clientY <= 0 || e.relatedTarget === null) {
                    firedE = true;
                    document.removeEventListener('mouseleave', onLeave);
                    document.removeEventListener('mouseout', onLeave);
                    showFn();
                }
            };
            document.addEventListener('mouseleave', onLeave);
            document.addEventListener('mouseout', onLeave);
            return;
        }
        showFn();
    }

    // ── Popup overlay ───────────────────────────────────────
    function showPopup(cfg, position, showOnce) {
        var overlay = document.createElement('div');
        overlay.id = ROOT_ID + '-overlay';
        overlay.className = 'syncro-form-overlay';
        var panel = document.createElement('div');
        panel.id = ROOT_ID;
        panel.className = 'syncro-form-container syncro-form-popup-panel pos-' + position;

        var closeBtn = document.createElement('button');
        closeBtn.className = 'syncro-form-close';
        closeBtn.innerHTML = '×';
        closeBtn.setAttribute('aria-label', 'Close');
        closeBtn.onclick = function() { close(); };
        panel.appendChild(closeBtn);

        overlay.appendChild(panel);
        overlay.addEventListener('click', function(e) { if (e.target === overlay) close(); });
        document.addEventListener('keydown', function esc(e) { if (e.key === 'Escape') { close(); document.removeEventListener('keydown', esc); } });
        document.body.appendChild(overlay);
        setTimeout(function(){ overlay.classList.add('visible'); }, 10);

        renderForm(cfg, panel, 'popup');
        trackView('popup');
        if (showOnce) localStorage.setItem('syncro_form_shown_' + FORM_ID, '1');

        function close() {
            overlay.classList.remove('visible');
            setTimeout(function(){ overlay.remove(); }, 250);
        }
    }

    // ── CSS injection (scoped) ─────────────────────────────
    function injectStyles(s, pos) {
        if (document.getElementById('syncro-form-styles-' + FORM_ID)) return;
        var st = document.createElement('style');
        st.id = 'syncro-form-styles-' + FORM_ID;
        var r = s.border_radius || 8;
        var scope = '#syncro-form-' + FORM_ID;
        st.innerHTML = [
            scope + ', ' + scope + ' * { box-sizing: border-box; font-family: "' + s.font_family + '", sans-serif; }',
            scope + ' { background:' + s.card_color + '; color:' + s.input_text_color + '; padding:20px; border-radius:' + (r+4) + 'px; }',
            scope + ' .sfx-field { margin-bottom:14px; }',
            scope + ' .sfx-label { display:block; font-size:13px; font-weight:600; color:' + s.label_color + '; margin-bottom:6px; }',
            scope + ' .sfx-label .sfx-req { color:#dc2626; }',
            scope + ' .sfx-help { font-size:11px; color:#9ca3af; margin-top:4px; }',
            scope + ' .sfx-input, ' + scope + ' .sfx-textarea, ' + scope + ' .sfx-select {',
            '  width:100%; padding:11px 14px; font-size:14px; font-family:inherit;',
            '  color:' + s.input_text_color + '; background:' + s.input_bg_color + ';',
            '  border:1.5px solid ' + s.input_border_color + '; border-radius:' + r + 'px; outline:none;',
            '  transition:border-color .15s;',
            '}',
            scope + ' .sfx-input:focus, ' + scope + ' .sfx-textarea:focus, ' + scope + ' .sfx-select:focus { border-color:' + s.brand_color + '; }',
            scope + ' .sfx-textarea { resize:vertical; min-height:80px; }',
            scope + ' .sfx-select { cursor:pointer; }',
            scope + ' .sfx-group { display:flex; flex-direction:column; gap:6px; }',
            scope + ' .sfx-group label { display:flex; align-items:center; gap:8px; font-size:13px; color:' + s.label_color + '; cursor:pointer; }',
            scope + ' .sfx-error { font-size:12px; color:#dc2626; margin-top:4px; min-height:1em; }',
            scope + ' .sfx-submit {',
            '  display:block; width:100%; padding:13px; font-size:15px; font-weight:700; font-family:inherit;',
            '  color:' + s.button_text_color + '; background:' + s.button_color + ';',
            '  border:none; border-radius:' + r + 'px; cursor:pointer; margin-top:18px; transition:opacity .15s;',
            '}',
            scope + ' .sfx-submit:hover { opacity:.9; }',
            scope + ' .sfx-submit:disabled { opacity:.5; cursor:not-allowed; }',
            scope + ' .sfx-heading { font-size:15px; font-weight:700; color:' + s.label_color + '; margin-bottom:8px; }',
            scope + ' .sfx-divider { border:none; border-top:1px solid ' + s.input_border_color + '; margin:16px 0; }',
            scope + ' .sfx-alert { background:#fef2f2; color:#dc2626; border:1px solid #fecaca; padding:10px 14px; border-radius:8px; font-size:13px; margin-bottom:12px; display:none; }',
            scope + ' .sfx-success { text-align:center; padding:20px; }',
            scope + ' .sfx-success-icon { width:52px; height:52px; border-radius:50%; background:#ecfdf5; display:flex; align-items:center; justify-content:center; margin:0 auto 14px; color:#059669; font-size:26px; font-weight:700; }',
            scope + ' .sfx-honey { position:absolute; left:-9999px; }',
            // Popup overlay
            '.syncro-form-overlay { position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:2147483600; display:flex; align-items:center; justify-content:center; opacity:0; transition:opacity .25s; padding:16px; }',
            '.syncro-form-overlay.visible { opacity:1; }',
            '.syncro-form-popup-panel { position:relative; width:100%; max-width:480px; max-height:90vh; overflow-y:auto; transform:translateY(10px); transition:transform .3s cubic-bezier(.4,0,.2,1); }',
            '.syncro-form-overlay.visible .syncro-form-popup-panel { transform:translateY(0); }',
            '.syncro-form-popup-panel.pos-bottom-right { position:fixed; right:20px; bottom:20px; max-width:380px; }',
            '.syncro-form-popup-panel.pos-bottom-left { position:fixed; left:20px; bottom:20px; max-width:380px; }',
            '.syncro-form-overlay.pos-bottom-right, .syncro-form-overlay.pos-bottom-left { background:transparent; pointer-events:none; } .syncro-form-overlay.pos-bottom-right > *, .syncro-form-overlay.pos-bottom-left > * { pointer-events:auto; }',
            '.syncro-form-close { position:absolute; top:10px; right:10px; width:28px; height:28px; border-radius:50%; border:none; background:rgba(0,0,0,.06); color:#374151; font-size:20px; line-height:1; cursor:pointer; display:flex; align-items:center; justify-content:center; z-index:10; }',
            '.syncro-form-close:hover { background:rgba(0,0,0,.12); }',
        ].join('\n');
        document.head.appendChild(st);
    }

    // ── Render form ─────────────────────────────────────────
    function renderForm(cfg, container, embedMode) {
        var state = {};
        var conds = cfg.conditional_logic || [];
        var html = '<div class="sfx-alert" data-sfx-alert></div>';
        html += '<form data-sfx-form novalidate>';
        html += '<input type="text" name="_website_url" class="sfx-honey" tabindex="-1" autocomplete="off">';
        (cfg.fields || []).forEach(function(f){ html += renderField(f); });
        html += '<button type="submit" class="sfx-submit">' + escapeHtml(cfg.labels?.submit || 'Enviar') + '</button>';
        html += '</form>';
        container.insertAdjacentHTML('beforeend', html);

        var form = container.querySelector('[data-sfx-form]');
        form.addEventListener('submit', function(e){
            e.preventDefault();
            submit(cfg, container, embedMode);
        });

        // Listen for input changes to re-eval conditions
        form.addEventListener('input', function(){ applyConditions(cfg, container); });
        form.addEventListener('change', function(){ applyConditions(cfg, container); });
        applyConditions(cfg, container);
    }

    function renderField(f) {
        var fid = f.id, t = f.type, label = f.label || '', ph = f.placeholder || '', req = f.required ? ' <span class="sfx-req">*</span>' : '';
        var help = f.help_text || '';
        var opts = f.options || [];

        if (t === 'divider') return '<hr class="sfx-divider" data-sfx-field="' + fid + '">';
        if (t === 'heading') return '<div class="sfx-heading" data-sfx-field="' + fid + '">' + escapeHtml(label) + '</div>';

        var inner = '';
        if (t === 'textarea') {
            inner = '<textarea class="sfx-textarea" name="' + fid + '" placeholder="' + escapeAttr(ph) + '"' + (f.required ? ' required' : '') + '></textarea>';
        } else if (t === 'select') {
            inner = '<select class="sfx-select" name="' + fid + '"' + (f.required ? ' required' : '') + '><option value="">' + escapeHtml(ph || '—') + '</option>' +
                opts.map(function(o){ return '<option value="' + escapeAttr(o) + '">' + escapeHtml(o) + '</option>'; }).join('') + '</select>';
        } else if (t === 'checkbox') {
            if (opts.length) {
                inner = '<div class="sfx-group">' + opts.map(function(o){ return '<label><input type="checkbox" name="' + fid + '[]" value="' + escapeAttr(o) + '"> ' + escapeHtml(o) + '</label>'; }).join('') + '</div>';
            } else {
                inner = '<div class="sfx-group"><label><input type="checkbox" name="' + fid + '" value="1"> ' + escapeHtml(label) + '</label></div>';
            }
        } else if (t === 'radio') {
            inner = '<div class="sfx-group">' + opts.map(function(o){ return '<label><input type="radio" name="' + fid + '" value="' + escapeAttr(o) + '"' + (f.required ? ' required' : '') + '> ' + escapeHtml(o) + '</label>'; }).join('') + '</div>';
        } else if (t === 'file') {
            inner = '<input type="file" class="sfx-input" name="' + fid + '"' + (f.required ? ' required' : '') + '>';
        } else {
            var inputType = t === 'tel' ? 'tel' : (t === 'email' ? 'email' : (t === 'number' ? 'number' : 'text'));
            inner = '<input type="' + inputType + '" class="sfx-input" name="' + fid + '" placeholder="' + escapeAttr(ph) + '"' + (f.required ? ' required' : '') + '>';
        }

        return '<div class="sfx-field" data-sfx-field="' + fid + '">' +
            '<label class="sfx-label">' + escapeHtml(label) + req + '</label>' +
            inner +
            (help ? '<div class="sfx-help">' + escapeHtml(help) + '</div>' : '') +
            '<div class="sfx-error" data-sfx-err="' + fid + '"></div>' +
            '</div>';
    }

    function applyConditions(cfg, container) {
        var conds = cfg.conditional_logic || [];
        var data = collectData(container);
        conds.forEach(function(c){
            if (!c || !c.target_field_id || !c.field_id) return;
            var el = container.querySelector('[data-sfx-field="' + c.target_field_id + '"]');
            if (!el) return;
            var v = data[c.field_id];
            var s = Array.isArray(v) ? v.join(',') : (v || '');
            var ok = true;
            switch (c.operator) {
                case 'equals': ok = (s === c.value); break;
                case 'not_equals': ok = (s !== c.value); break;
                case 'contains': ok = (s.toLowerCase().indexOf((c.value || '').toLowerCase()) >= 0); break;
                case 'not_empty': ok = (s !== ''); break;
                case 'is_empty': ok = (s === ''); break;
            }
            el.style.display = ok ? '' : 'none';
        });
    }

    function collectData(container) {
        var form = container.querySelector('[data-sfx-form]');
        var data = {};
        form.querySelectorAll('input, textarea, select').forEach(function(el){
            if (!el.name) return;
            if (el.name === '_website_url') { data._website_url = el.value; return; }
            if (el.type === 'checkbox') {
                var key = el.name.replace('[]', '');
                if (!data[key]) data[key] = [];
                if (el.checked) data[key].push(el.value);
            } else if (el.type === 'radio') {
                if (el.checked) data[el.name] = el.value;
            } else {
                data[el.name] = el.value;
            }
        });
        return data;
    }

    function submit(cfg, container, embedMode) {
        var btn = container.querySelector('.sfx-submit');
        var alert = container.querySelector('[data-sfx-alert]');
        btn.disabled = true;
        alert.style.display = 'none';
        container.querySelectorAll('[data-sfx-err]').forEach(function(e){ e.textContent = ''; });

        var data = collectData(container);
        // UTMs
        try {
            var qs = new URLSearchParams(window.location.search);
            ['utm_source','utm_medium','utm_campaign','utm_term','utm_content','fbclid','gclid'].forEach(function(k){
                var v = qs.get(k);
                if (v) data['_' + k] = v;
            });
        } catch(e){}
        data._embed_mode = embedMode;
        data._referrer   = document.referrer || window.location.href;

        fetch(cfg.submit_url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(data),
        }).then(function(r){ return r.json(); }).then(function(d){
            if (d && d.success) {
                if (d.confirmation_type === 'redirect' && d.confirmation_value) {
                    var url = d.confirmation_value;
                    if (!/^https?:\\/\\//i.test(url)) url = 'https://' + url;
                    window.location.href = url;
                    return;
                }
                container.innerHTML = '<div class="sfx-success"><div class="sfx-success-icon">✓</div><div style="font-size:15px;font-weight:700;color:#1a1d23;margin-bottom:6px;">' + escapeHtml(d.confirmation_value || '') + '</div></div>';
            } else if (d && d.errors) {
                Object.keys(d.errors).forEach(function(k){
                    var errEl = container.querySelector('[data-sfx-err="' + k + '"]');
                    if (errEl) errEl.textContent = (d.errors[k][0] || '');
                });
                btn.disabled = false;
            } else {
                alert.textContent = (d && d.message) || 'Erro ao enviar';
                alert.style.display = 'block';
                btn.disabled = false;
            }
        }).catch(function(){
            alert.textContent = 'Erro de conexão';
            alert.style.display = 'block';
            btn.disabled = false;
        });
    }

    function trackView(mode) {
        try {
            var key = 'syncro_form_viewed_' + FORM_ID + '_' + mode;
            if (sessionStorage.getItem(key)) return;
            sessionStorage.setItem(key, '1');
            fetch(TRACK_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ mode: mode }),
                keepalive: true,
            });
        } catch(e){}
    }

    function escapeHtml(s) { return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
    function escapeAttr(s) { return escapeHtml(s); }
})();
JS;
    }
}
