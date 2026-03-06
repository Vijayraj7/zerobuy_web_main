@php
    $generaleSetting = App\Models\GeneraleSetting::first();

    $title = $generaleSetting?->title ?? config('app.name', 'ReadyEcommerce');
    $favicon = $generaleSetting?->favicon ?? asset('assets/favicon.png');
@endphp
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="base-url" content="{{ url('/') }}">
    <meta name="app-url" content="{{ url('/') }}">
    <!-- description -->
    <meta name="description" content="ecommerce website">

    <title>{{ $title }}</title>
    <link rel="shortcut icon" href="{{ $favicon }}" type="image/x-icon">
    <script type="text/javascript">
        (function(c, l, a, r, i, t, y) {
            c[a] = c[a] || function() {
                (c[a].q = c[a].q || []).push(arguments)
            };
            t = l.createElement(r);
            t.async = 1;
            t.src = "https://www.clarity.ms/tag/" + i;
            y = l.getElementsByTagName(r)[0];
            y.parentNode.insertBefore(t, y);
        })(window, document, "clarity", "script", "vrh02lfdix");
    </script>

    @vite('resources/css/app.css')
</head>

<body>
    <div id="app"></div>

    @vite('resources/js/app.js')
</body>

</html>
