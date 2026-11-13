{{-- Inisialisasi Bootstrap Tooltip global (aman untuk Livewire morph) --}}
<script>
    (function () {
        'use strict';

        var TOOLTIP_OPTIONS = {
            container: 'body',      // tidak terpotong oleh .table-responsive/overflow
            trigger: 'hover focus', // muncul saat pointer di atas tombol, hilang saat keluar
            placement: 'top',
            delay: { show: 150, hide: 60 },
            boundary: 'viewport'
        };

        function initTooltips(root) {
            var scope = root && root.querySelectorAll ? root : document;
            scope.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
                try {
                    if (!window.bootstrap || !bootstrap.Tooltip) {
                        return;
                    }
                    var inst = bootstrap.Tooltip.getInstance(el);
                    if (inst) {
                        // Livewire morph MENGHAPUS atribut aria-describedby (set oleh Bootstrap)
                        // sehingga instance tetap ada tapi elemen tip-nya yatim → hover
                        // terkunci diam. Deteksi inkonsistensi ini lalu dispose + buat ulang.
                        var tipId = el.getAttribute('aria-describedby');
                        var tipEl = tipId ? document.getElementById(tipId) : null;
                        if (!tipEl) {
                            inst.dispose();
                            inst = null;
                        }
                    }
                    if (!inst) {
                        new bootstrap.Tooltip(el, TOOLTIP_OPTIONS);
                    }
                } catch (e) {
                    // abaikan satu elemen bermasalah, sisanya tetap jalan
                }
            });
        }

        function disposeTooltips(root) {
            var scope = root && root.querySelectorAll ? root : document;
            scope.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
                var instance = window.bootstrap && bootstrap.Tooltip ? bootstrap.Tooltip.getInstance(el) : null;
                if (instance) {
                    instance.dispose();
                }
            });
        }

        // Buang tooltip yang "tertinggal" karena pemiliknya di-morph/dihapus dari DOM
        function disposeOrphans() {
            document.querySelectorAll('.tooltip').forEach(function (tip) {
                if (tip.id && document.querySelector('[aria-describedby="' + tip.id + '"]')) {
                    return;
                }
                tip.remove();
            });
        }

        function debounce(fn, ms) {
            var timer;
            return function () {
                clearTimeout(timer);
                timer = setTimeout(fn, ms);
            };
        }

        var bootInit = debounce(function () {
            initTooltips(document);
            disposeOrphans();
        }, 40);

        document.addEventListener('DOMContentLoaded', bootInit);
        document.addEventListener('livewire:load', bootInit);
        document.addEventListener('livewire:navigated', bootInit);

        if (window.Livewire && typeof Livewire.hook === 'function') {
            Livewire.hook('morphed', function () { bootInit(); });
            Livewire.hook('morph.removed', function (ctx) {
                if (ctx && ctx.el) {
                    disposeTooltips(ctx.el);
                }
                bootInit();
            });
        }

        // Pengaman untuk mutasi DOM yang tidak lewat Livewire (querySelectorAll cukup, MutationObserver selaras)
        var mo = new MutationObserver(bootInit);
        mo.observe(document.documentElement, { childList: true, subtree: true });

        // Pengaman periodik untuk tooltip yatim (pemilik sudah di-morph)
        setInterval(disposeOrphans, 1000);
    })();
</script>
