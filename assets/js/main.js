(function () {
    'use strict';
    document.addEventListener('DOMContentLoaded', function () {
        if (window.lucide) lucide.createIcons();
        document.querySelectorAll('[data-confirm]').forEach(function (el) {
            el.addEventListener('click', function (event) {
                if (!window.confirm(el.dataset.confirm || 'Confirmar esta ação?')) event.preventDefault();
            });
        });
        document.querySelectorAll('form[data-loading]').forEach(function (form) {
            form.addEventListener('submit', function () {
                const btn = form.querySelector('button[type="submit"]');
                if (!btn || btn.disabled) return;
                btn.disabled = true;
                btn.dataset.original = btn.innerHTML;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Salvando...';
            });
        });
        document.querySelectorAll('[data-search-table]').forEach(function (input) {
            const table = document.querySelector(input.dataset.searchTable);
            if (!table) return;
            input.addEventListener('input', function () {
                const q = input.value.toLocaleLowerCase('pt-BR').trim();
                table.querySelectorAll('tbody tr').forEach(function (row) {
                    row.hidden = q && !row.textContent.toLocaleLowerCase('pt-BR').includes(q);
                });
            });
        });
    });

    window.apiPost = async function (url, data) {
        const body = data instanceof FormData ? data : new URLSearchParams(data);
        const response = await fetch(url, {method: 'POST', body: body, headers: {'X-Requested-With': 'XMLHttpRequest'}});
        let payload;
        try { payload = await response.json(); } catch (_) { throw new Error('Resposta inválida do servidor.'); }
        if (!response.ok || !payload.success) throw new Error(payload.message || 'Não foi possível concluir a operação.');
        return payload;
    };

    window.notifyError = function (error) {
        alert(error && error.message ? error.message : 'Não foi possível concluir a operação.');
    };
})();
