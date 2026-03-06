@php
    $directory = app()->getLocale() == 'ar' ? 'rtl' : 'ltr';
@endphp
<!DOCTYPE html>
<html lang="en" dir="{{ $directory }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="shortcut icon" type="image/png" href="{{ $generaleSetting?->favicon ?? asset('assets/favicon.png') }}" />
    <title>{{ $generaleSetting?->title ?? config('app.name', 'Laravel') }}</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="all,follow">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('assets/css/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/sweetalert2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/quill.snow.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/responsive.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/toastr.min.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/jquery.timepicker.min.css') }}" type="text/css" />
    <link rel="stylesheet" href="{{ asset('assets/css/jquery-ui.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('assets/css/dataTables.bootstrap5.min.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('assets/css/responsive.bootstrap5.min.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('assets/css/buttons.bootstrap5.min.css') }}" type="text/css">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">

    @stack('css')
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
</head>

<body class="loading">
    <div class="container-fluid py-4">
        @yield('content')
    </div>

    <script src="{{ asset('assets/scripts/jquery-3.6.3.min.js') }}"></script>
    <script src="{{ asset('assets/scripts/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/scripts/script.js') }}"></script>
    <script src="{{ asset('assets/scripts/main.js') }}"></script>
    <script src="{{ asset('assets/scripts/full-screen.js') }}"></script>
    <script src="{{ asset('assets/scripts/select2.min.js') }}"></script>
    <script src="{{ asset('assets/scripts/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('assets/scripts/quill.js') }}"></script>
    <script src="{{ asset('assets/scripts/jQuery.print.min.js') }}"></script>
    <script src="{{ asset('assets/scripts/toastr.min.js') }}"></script>
    <script src="{{ asset('assets/scripts/jquery.timepicker.min.js') }}"></script>
    <script src="{{ asset('assets/scripts/jquery-ui.js') }}"></script>
    <script src="{{ asset('assets/scripts/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/scripts/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('assets/scripts/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('assets/scripts/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('assets/scripts/buttons.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('assets/scripts/jszip.min.js') }}"></script>
    <script src="{{ asset('assets/scripts/pdfmake.min.js') }}"></script>
    <script src="{{ asset('assets/scripts/vfs_fonts.js') }}"></script>
    <script src="{{ asset('assets/scripts/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('assets/scripts/buttons.print.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>
    <script src="{{ asset('assets/scripts/custom.js') }}"></script>

    <script>
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });
    </script>

    @stack('scripts')
</body>

</html>
