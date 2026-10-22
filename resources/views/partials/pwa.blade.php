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
</script>
