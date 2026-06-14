// Contagem regressiva até o kickoff e travamento visual ao expirar.
// O bloqueio REAL é feito no servidor; isto é apenas UX.
(function () {
    'use strict';

    function fmt(ms) {
        if (ms <= 0) return null;
        var s = Math.floor(ms / 1000);
        var d = Math.floor(s / 86400); s -= d * 86400;
        var h = Math.floor(s / 3600);  s -= h * 3600;
        var m = Math.floor(s / 60);    s -= m * 60;
        if (d > 0) return 'fecha em ' + d + 'd ' + h + 'h';
        if (h > 0) return 'fecha em ' + h + 'h ' + m + 'min';
        if (m > 0) return 'fecha em ' + m + 'min';
        return 'fecha em ' + s + 's';
    }

    function tick() {
        var now = Date.now();
        document.querySelectorAll('[data-kickoff]').forEach(function (el) {
            var t = Date.parse(el.getAttribute('data-kickoff'));
            if (isNaN(t)) return;
            var label = fmt(t - now);
            var match = el.closest('.match');
            if (label === null) {
                el.textContent = '🔒 fechado';
                if (match) {
                    match.querySelectorAll('input, button[type="submit"]').forEach(function (i) {
                        i.disabled = true;
                    });
                }
            } else {
                el.textContent = '⏳ ' + label;
            }
        });
    }

    tick();
    setInterval(tick, 30000);
})();

// Ao abrir /jogos, rola até os jogos de hoje (ou o próximo dia com jogos).
// Não interfere quando há âncora #m... na URL (volta de um palpite salvo).
(function () {
    'use strict';
    if (window.location.hash) return;
    var target = document.querySelector('[data-scroll-target]');
    if (target) target.scrollIntoView({ block: 'start' });
})();

// Salva o palpite automaticamente ao sair do campo (blur), para que ninguém
// perca placares por não clicar em "Salvar/Atualizar" em cada jogo.
(function () {
    'use strict';

    var toastEl = null, toastTimer = null;
    function toast(msg, isErr) {
        if (!toastEl) {
            toastEl = document.createElement('div');
            toastEl.className = 'autosave-toast';
            toastEl.setAttribute('role', 'status');
            document.body.appendChild(toastEl);
        }
        toastEl.textContent = msg;
        toastEl.classList.toggle('is-err', !!isErr);
        void toastEl.offsetWidth; // reinicia a transição
        toastEl.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { toastEl.classList.remove('show'); }, 2000);
    }

    document.querySelectorAll('.match form').forEach(function (form) {
        var home = form.querySelector('[name="pred_home"]');
        var away = form.querySelector('[name="pred_away"]');
        var btn  = form.querySelector('button[type="submit"]');
        if (!home || !away) return;

        var saved = home.value + '×' + away.value; // snapshot do que está salvo

        function trySave() {
            if (home.disabled || away.disabled) return;          // jogo fechado
            if (home.value === '' || away.value === '') return;   // precisa dos dois
            var current = home.value + '×' + away.value;
            if (current === saved) return;                        // nada mudou
            saved = current;                                      // evita reenvio em rajada

            fetch(form.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(form),
                credentials: 'same-origin'
            }).then(function (r) {
                return r.json().then(function (data) { return { ok: r.ok, data: data }; });
            }).then(function (res) {
                if (res.ok && res.data && res.data.ok) {
                    if (btn) btn.textContent = 'Atualizar';
                    var card = form.closest('.match');
                    if (card) {
                        card.classList.add('saved-flash');
                        setTimeout(function () { card.classList.remove('saved-flash'); }, 1200);
                    }
                    toast('Palpite salvo ✓', false);
                } else {
                    saved = null;                                 // permite tentar de novo
                    toast((res.data && res.data.error) || 'Não foi possível salvar.', true);
                }
            }).catch(function () {
                saved = null;
                toast('Sem conexão — palpite não salvo.', true);
            });
        }

        home.addEventListener('blur', trySave);
        away.addEventListener('blur', trySave);
    });
})();
