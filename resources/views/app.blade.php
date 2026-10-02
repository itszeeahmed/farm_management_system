<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>GreenPastures Agro — Enterprise Livestock &amp; Dairy Platform</title>
    
    <!-- Google Fonts: Inter for UI, JetBrains Mono for data -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    
    <script>
        // Apply saved theme before render to avoid flash
        (function() {
            const dark = localStorage.getItem('farm_theme') === 'dark';
            if (dark) document.documentElement.classList.add('dark');
        })();
        window.__FARM_CONFIG__ = {
            baseUrl: '{{ url('/') }}',
            apiUrl: '{{ url('/api/v1') }}',
            csrfToken: '{{ csrf_token() }}'
        };
    </script>

    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body class="min-h-screen antialiased">
    <div id="root"></div>
</body>
</html>
