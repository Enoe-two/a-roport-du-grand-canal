document.addEventListener('DOMContentLoaded', function () {

    // ── Mobile nav toggle ──────────────────────────────────────────
    var toggle = document.getElementById('navToggle');
    var menu   = document.getElementById('navMenu');
    if (toggle && menu) {
        toggle.addEventListener('click', function (e) {
            e.stopPropagation();
            menu.classList.toggle('open');
        });
        document.addEventListener('click', function (e) {
            if (!menu.contains(e.target) && !toggle.contains(e.target)) {
                menu.classList.remove('open');
            }
        });
    }

    // ── Auto-hide flash messages après 5s ─────────────────────────
    document.querySelectorAll('.flash').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity .5s';
            el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 500);
        }, 5000);
    });

    // ── Fermer modals en cliquant en dehors ───────────────────────
    document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) overlay.classList.remove('open');
        });
    });

});

function openModal(id) {
    var m = document.getElementById(id);
    if (m) m.classList.add('open');
}
function closeModal(id) {
    var m = document.getElementById(id);
    if (m) m.classList.remove('open');
}
