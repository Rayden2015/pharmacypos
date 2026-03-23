<!doctype html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!--favicon-->
    <link rel="icon" href="{{asset('dash/img/icon.jpg')}}" type="image/png" />



    <link href="{{ asset('dash/plugins/simplebar/css/simplebar.css') }}" rel="stylesheet" />
    <link href="{{ asset('dash/plugins/perfect-scrollbar/css/perfect-scrollbar.css') }}" rel="stylesheet" />
    <link href="{{ asset('dash/plugins/metismenu/css/metisMenu.min.css') }}" rel="stylesheet" />

    <link href="{{ asset('dash/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
    <!-- loader-->
    <link href="{{ asset('dash/css/pace.min.css') }}" rel="stylesheet" />
    <script src="{{ asset('dash/js/pace.min.js') }}"></script>
    <link href="{{ asset('dash/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link href="{{ asset('dash/css/app-1.css') }}" rel="stylesheet">
    <link href="{{ asset('dash/css/icons-1.css') }}" rel="stylesheet">

    <link href="{{asset('dash/plugins/Drag-And-Drop/dist/imageuploadify.min-1.css')}}" rel="stylesheet">

    <link href="{{ asset('dash/plugins/select2/css/select2.min.css') }}" rel="stylesheet">
    <link href="{{ asset('dash/plugins/select2/css/select2-bootstrap4.css') }}" rel="stylesheet">

    <link href="{{ asset('dash/css/header-alerts.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('dash/css/dark-theme-1.css') }}" />
    <link rel="stylesheet" href="{{ asset('dash/css/semi-dark-1.css') }}" />
    <link rel="stylesheet" href="{{ asset('dash/css/header-colors-1.css') }}" />


    <title>{{ config('app.name', 'Laravel') }}</title>
    
</head>

<body>

    @include('inc.header')
    @yield('content')



    <!-- Bootstrap JS -->
    <script src="{{ asset('dash/js/bootstrap.bundle.min.js') }}"></script>
    <!--plugins-->
    <script src="{{ asset('dash/js/jquery.js') }}"></script>
    <script src="{{ asset('dash/js/popper.min.js') }}"></script>
    <script src="{{ asset('dash/js/jquery.min.js') }}"></script>
    <script src="{{ asset('dash/plugins/simplebar/js/simplebar.min.js') }}"></script>
    <script src="{{ asset('dash/plugins/metismenu/js/metisMenu.min.js') }}"></script>
    <script src="{{ asset('dash/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
    <script src="{{ asset('dash/plugins/chartjs/js/Chart.min.js') }}"></script>
    <script src="{{ asset('dash/plugins/chartjs/js/Chart.extension.js') }}"></script>



    {{-- <script src="{{ asset('dash/js/bootstrap.bundle.min-1.js') }}"></script> --}}
    <!--plugins-->
    {{-- <script src="{{ asset('dash/js/jquery.min-1.js') }}"></script>
    <script src="{{ asset('dash/plugins/simplebar/js/simplebar.min-1.js') }}"></script>
    <script src="{{ asset('dash/plugins/metismenu/js/metisMenu.min-1.js') }}"></script>
    <script src="{{ asset('dash/plugins/perfect-scrollbar/js/perfect-scrollbar-1.js') }}"></script> --}}
    <script src="{{ asset('dash/plugins/select2/js/select2.min.js') }}"></script>
    <script>
        $('.single-select').select2({
            theme: 'bootstrap4',
            width: $(this).data('width') ? $(this).data('width') : $(this).hasClass('w-100') ? '100%' : 'style',
            placeholder: $(this).data('placeholder'),
            allowClear: Boolean($(this).data('allow-clear')),
        });
        $('.multiple-select').select2({
            theme: 'bootstrap4',
            width: $(this).data('width') ? $(this).data('width') : $(this).hasClass('w-100') ? '100%' : 'style',
            placeholder: $(this).data('placeholder'),
            allowClear: Boolean($(this).data('allow-clear')),
        });

    </script>
    <!--app JS-->
    {{-- <script src="{{asset('dash/js/app-1.js')}}"></script> --}}


    <!--app JS-->
    <script src="{{ asset('dash/js/app.js') }}"></script>
    <script src="{{ asset('dash/js/header-alerts.js') }}"></script>
    <script src="{{asset('dash/plugins/Drag-And-Drop/dist/imageuploadify.min-1.js')}}"></script>
	<script>
		$(document).ready(function () {
			$('#image-uploadify').imageuploadify();
		})
	</script>



    <script src="{{ asset('dash/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('dash/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            $('#example').DataTable();
        });

    </script>
    <script>
        $(document).ready(function() {
            var table = $('#example2').DataTable({
                bInfo: false,
                paging: false,//Dont want paging                
    bPaginate: false,//Dont want paging 
                lengthChange: false,
                buttons: ['copy', 'excel', 'pdf', 'print']
            });

            table.buttons().container()
                .appendTo('#example2_wrapper .col-md-6:eq(0)');
                
        });

	</script>

<script>
    $(document).ready(function() {
        var table = $('#example3').DataTable({
            lengthChange: false,
            buttons: ['copy', 'excel', 'pdf', 'print']
        });

        table.buttons().container()
            .appendTo('#example3_wrapper .col-md-6:eq(0)');
    });

</script>
	
	

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
                    new bootstrap.Tooltip(el);
                });
            }
        });
    </script>
</body>
@yield('script')

</html>
