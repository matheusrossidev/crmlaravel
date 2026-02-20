import './bootstrap';

// Laravel Echo + Reverb WebSocket
// Config is injected server-side via window.reverbConfig in the Blade layout
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const _rcfg = window.reverbConfig ?? {};
window.Echo = new Echo({
    broadcaster: 'reverb',
    key: _rcfg.key,
    wsHost: _rcfg.wsHost,
    wsPort: _rcfg.wsPort ?? 443,
    wssPort: _rcfg.wssPort ?? 443,
    forceTLS: _rcfg.forceTLS ?? true,
    enabledTransports: ['ws', 'wss'],
});

// jQuery global
import $ from 'jquery';
window.$ = window.jQuery = $;

// AdminLTE
import 'admin-lte';

// Chart.js
import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);
window.Chart = Chart;

// Toastr
import toastr from 'toastr';
window.toastr = toastr;

// Configurações padrão do Toastr
toastr.options = {
    closeButton: true,
    progressBar: true,
    positionClass: 'toast-bottom-right',
    timeOut: 4000,
    newestOnTop: false,
    preventDuplicates: false,
    showDuration: 200,
    hideDuration: 300,
    extendedTimeOut: 1000,
};

// AJAX global — injeta CSRF token em todas as requisições
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        'Accept': 'application/json',
    }
});

// API helper global
window.API = {
    call: function(method, url, data = null) {
        return $.ajax({
            url: url,
            method: method,
            data: data ? JSON.stringify(data) : null,
            contentType: 'application/json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json',
            },
        }).fail(function(xhr) {
            if (xhr.status === 422) {
                const errors = xhr.responseJSON?.errors ?? {};
                Object.keys(errors).forEach(function(field) {
                    toastr.error(errors[field][0]);
                });
            } else if (xhr.status === 403) {
                toastr.error('Você não tem permissão para esta ação.');
            } else if (xhr.status === 429) {
                toastr.warning('Muitas requisições. Aguarde um momento.');
            } else if (xhr.status !== 0) {
                toastr.error('Erro inesperado. Tente novamente.');
            }
        });
    },
    get: function(url, data) { return this.call('GET', url, data); },
    post: function(url, data) { return this.call('POST', url, data); },
    put: function(url, data) { return this.call('PUT', url, data); },
    delete: function(url) { return this.call('DELETE', url); },
};

// Utilitário de escape HTML
window.escapeHtml = function(text) {
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text ?? '').replace(/[&<>"']/g, m => map[m]);
};
