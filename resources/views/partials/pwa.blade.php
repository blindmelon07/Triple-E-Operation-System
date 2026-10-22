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

        document.addEventListener('click', async (event) => {
            if (! event.target.closest('[data-pwa-install]')) return;

            if (deferredPrompt) {
                deferredPrompt.prompt();
                await deferredPrompt.userChoice;
                deferredPrompt = null;
                refresh();
            } else if (ios) {
                alert('To install Tri-E on your iPhone or iPad:\n\n1. Tap the Share button (square with an arrow) in Safari\n2. Tap "Add to Home Screen"\n3. Tap "Add"');
            }
        });

        document.addEventListener('DOMContentLoaded', refresh);
        // Filament swaps page content without a full reload.
        document.addEventListener('livewire:navigated', refresh);
    })();
</script>
