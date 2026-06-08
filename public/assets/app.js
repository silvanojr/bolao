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
