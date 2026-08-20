<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Promarine">
<link rel="manifest" href="/site.webmanifest">
<link rel="icon" href="/assets/promarine/promarine-app-icon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/assets/promarine/optimized/promarine-urchin-320.webp">
@if(request()->is('admin*') || request()->is('mi-plan*'))<meta name="robots" content="noindex,nofollow">@endif
<title>{{ $title ?? 'Promarine Suscripciones' }}</title>
{{-- Inline theme script: aplica pm-theme-dark al <html> antes de que el CSS cargue → elimina flash --}}
<script>
(function(){
  var t = localStorage.getItem('promarine-theme');
  var isDark = t !== 'light'; // default oscuro
  var html = document.documentElement;
  // Al correr en <head>, body aún no existe → aplicamos al <html>
  if (isDark) { html.classList.add('pm-theme-dark'); }
  // Actualizar meta theme-color
  var themeColorMeta = document.querySelector('meta[name="theme-color"]');
  if (!themeColorMeta) {
    themeColorMeta = document.createElement('meta');
    themeColorMeta.name = 'theme-color';
    document.head.appendChild(themeColorMeta);
  }
  themeColorMeta.content = isDark ? '#020c19' : '#f7fafb';
  var colorSchemeMeta = document.querySelector('meta[name="color-scheme"]');
  if (!colorSchemeMeta) {
    colorSchemeMeta = document.createElement('meta');
    colorSchemeMeta.name = 'color-scheme';
    document.head.appendChild(colorSchemeMeta);
  }
  colorSchemeMeta.content = isDark ? 'dark' : 'light';
})();
</script>
<script defer src="https://analytics.influencergrowthsystem.com/script.js" data-website-id="6eb9fce5-b857-4a3c-9f5a-73d9200a7804"></script>
@vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="font-sans antialiased pm-site" x-data x-init="
  (function(){
    var t = localStorage.getItem('promarine-theme');
    var isDark = t !== 'light';
    if (isDark) {
      document.body.classList.add('pm-theme-dark');
    }
    // Swap theme images on non-landing pages
    document.querySelectorAll('.pm-theme-image').forEach(function(img){
      var src = isDark ? img.dataset.darkSrc : img.dataset.lightSrc;
      if (src) img.src = src;
    });
  })()
">{{ $slot }}</body>
</html>

