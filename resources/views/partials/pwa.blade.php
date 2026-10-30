<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#1e40af">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Tri-E">
<link rel="apple-touch-icon" href="/images/apple-touch-icon.png">
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js'));
    }

    // "Install App" buttons (partials.pwa-install-button). Chrome/Edge/Android fire
    // beforeinstallprompt when the app can be installed; iOS Safari never does, so there
    // the button shows Add to Home Screen instructions instead. Hidden once installed.
    (() => {
        const standalone = window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
        const ios = /iphone|ipad|ipod/i.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
        let deferredPrompt = null;

        const buttons = () => document.querySelectorAll('[data-pwa-install]');
        const show = (visible) => buttons().forEach((b) => b.style.display = visible ? 'inline-flex' : 'none');
        const refresh = () => show(!standalone && (deferredPrompt !== null || ios));

        window.addEventListener('beforeinstallprompt', (event) => {
            event.preventDefault();
            deferredPrompt = event;
            refresh();
        });

        window.addEventListener('appinstalled', () => {
            deferredPrompt = null;
            show(false);
        });

        // iOS instructions are an on-page panel, not alert(): Safari silently drops alerts
        // once the user has chosen to block dialogs, which made the button look dead.
        const showIosHelp = () => {
            if (document.getElementById('pwa-ios-help')) return;

            const overlay = document.createElement('div');
            overlay.id = 'pwa-ios-help';
            overlay.style.cssText = 'position:fixed; inset:0; z-index:99999; display:flex; align-items:center; justify-content:center; padding:16px; background:rgba(0,0,0,.5);';
            overlay.innerHTML = `
                <div style="max-width:360px; width:100%; padding:20px; border-radius:12px; background:#fff; color:#111827; font-family:system-ui,sans-serif; font-size:15px; line-height:1.5; box-shadow:0 10px 30px rgba(0,0,0,.3);">
                    <div style="margin-bottom:12px; font-size:17px; font-weight:700;">Install Tri-E on your iPhone or iPad</div>
                    <ol style="margin:0 0 16px; padding-left:20px;">
                        <li>Open this page in <b>Safari</b></li>
                        <li>Tap the <b>Share</b> button (square with an arrow &#x2191;)</li>
                        <li>Scroll down and tap <b>Add to Home Screen</b></li>
                        <li>Tap <b>Add</b></li>
                    </ol>
                    <button type="button" data-pwa-close style="width:100%; padding:10px; border:0; border-radius:8px; background:#1e40af; color:#fff; font-size:15px; font-weight:600; cursor:pointer;">OK</button>
                </div>`;
            overlay.addEventListener('click', (event) => {
                if (event.target === overlay || event.target.closest('[data-pwa-close]')) overlay.remove();
            });
            document.body.appendChild(overlay);
        };

        const install = async (event) => {
            event.preventDefault();
            event.stopPropagation();

            if (deferredPrompt) {
                deferredPrompt.prompt();
                await deferredPrompt.userChoice;
                deferredPrompt = null;
                refresh();
            } else if (ios) {
                showIosHelp();
            }
        };

        // Bound directly to each button (not delegated from document) so Filament's
        // user-menu/dropdown handlers can't swallow the tap first.
        const bind = () => buttons().forEach((b) => {
            if (b.dataset.pwaBound) return;
            b.dataset.pwaBound = '1';
            b.addEventListener('click', install);
        });

        const init = () => { bind(); refresh(); };

        document.addEventListener('DOMContentLoaded', init);
        // Filament swaps page content without a full reload.
        document.addEventListener('livewire:navigated', init);
    })();
</script>
