<!DOCTYPE html>
<!-- @php
use Illuminate\Support\Facades\Cache;
if (!empty(Cache::get('guestlang'))) {
    App::setLocale(Cache::get('guestlang'));
    $guestlang = Cache::get('guestlang');
} else {
    App::setLocale(env('APP_LANG'));
    $guestlang = env('APP_LANG');
}
@endphp -->
<html class="loading {{ env('APP_THEME') }}" lang="{{ $guestlang }}" dir="ltr">
<!-- BEGIN: Head-->

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1.0,user-scalable=0,minimal-ui">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Wazone Whatsapp Gateway by visimisi.net, simple but powerful, handles complex tasks, clean and easy to read codes.">
    <meta name="keywords" content="wazone gateway, multi device, baileys, multi sessions, multi users">
    <meta name="author" content="ARROCY">
    <title>{{ env('APP_NAME') }}</title>
    <link rel="apple-touch-icon" href="{{ str_replace('/public','',url('/Modules/Wazone')) }}/app-assets/images/ico/apple-icon-120.png">
    <link rel="shortcut icon" type="image/x-icon" href="{{ str_replace('/public','',url('/Modules/Wazone')) }}/app-assets/images/ico/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;1,400;1,500;1,600" rel="stylesheet">

    <!-- BEGIN: Vendor CSS-->
    <link rel="stylesheet" type="text/css" href="{{ str_replace('/public','',url('/Modules/Wazone')) }}/app-assets/vendors/css/vendors.min.css">
    <!-- END: Vendor CSS-->

    <!-- BEGIN: Theme CSS-->
    <link rel="stylesheet" type="text/css" href="{{ str_replace('/public','',url('/Modules/Wazone')) }}/app-assets/css/bootstrap.css">
    <link rel="stylesheet" type="text/css" href="{{ str_replace('/public','',url('/Modules/Wazone')) }}/app-assets/css/bootstrap-extended.css">
    <link rel="stylesheet" type="text/css" href="{{ str_replace('/public','',url('/Modules/Wazone')) }}/app-assets/css/colors.css">
    <link rel="stylesheet" type="text/css" href="{{ str_replace('/public','',url('/Modules/Wazone')) }}/app-assets/css/components.css">
    <link rel="stylesheet" type="text/css" href="{{ str_replace('/public','',url('/Modules/Wazone')) }}/app-assets/css/themes/dark-layout.css">
    <link rel="stylesheet" type="text/css" href="{{ str_replace('/public','',url('/Modules/Wazone')) }}/app-assets/css/themes/bordered-layout.css">
    <link rel="stylesheet" type="text/css" href="{{ str_replace('/public','',url('/Modules/Wazone')) }}/app-assets/css/themes/semi-dark-layout.css">

    <!-- BEGIN: Page CSS-->
    <link rel="stylesheet" type="text/css" href="{{ str_replace('/public','',url('/Modules/Wazone')) }}/app-assets/css/core/menu/menu-types/vertical-menu.css">
    <!-- END: Page CSS-->

    <!-- BEGIN: Custom CSS-->
    <link rel="stylesheet" type="text/css" href="{{ str_replace('/public','',url('/Modules/Wazone')) }}/assets/css/style.css">
    <!-- END: Custom CSS-->

</head>
<!-- END: Head-->

<!-- BEGIN: Body-->

<body class="vertical-layout vertical-menu-modern  navbar-floating footer-static  " data-open="click" data-menu="vertical-menu-modern" data-col="">

    <!-- BEGIN: Header-->
    <nav class="header-navbar navbar navbar-expand-lg align-items-center floating-nav navbar-light navbar-shadow container-xxl">
        <div class="navbar-container d-flex content">
            <ul class="nav navbar-nav d-xl-none">
                <li class="nav-item"><a class="nav-link menu-toggle" href="#"><i class="ficon" data-feather="menu"></i></a></li>
            </ul>
        </div>
    </nav>
    <!-- END: Header-->

    <!-- BEGIN: Main Menu-->
    <div class="main-menu menu-fixed menu-light menu-accordion menu-shadow" data-scroll-to-active="true">
        <div class="navbar-header">
            <ul class="nav navbar-nav flex-row">
                <li class="nav-item me-auto">
                    <a class="navbar-brand" href="{{ url('/') }}">
                        <span class="brand-logo">
                            <defs>
                                <lineargradient id="linearGradient-1" x1="100%" y1="10.5120544%" x2="50%" y2="89.4879456%">
                                    <stop stop-color="#000000" offset="0%"></stop>
                                    <stop stop-color="#FFFFFF" offset="100%"></stop>
                                </lineargradient>
                                <lineargradient id="linearGradient-2" x1="64.0437835%" y1="46.3276743%" x2="37.373316%" y2="100%">
                                    <stop stop-color="#EEEEEE" stop-opacity="0" offset="0%"></stop>
                                    <stop stop-color="#FFFFFF" offset="100%"></stop>
                                </lineargradient>
                            </defs>
                            <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                <g id="Artboard" transform="translate(-400.000000, -178.000000)">
                                    <g id="Group" transform="translate(400.000000, 178.000000)">
                                        <path class="text-primary" id="Path" d="M-5.68434189e-14,2.84217094e-14 L39.1816085,2.84217094e-14 L69.3453773,32.2519224 L101.428699,2.84217094e-14 L138.784583,2.84217094e-14 L138.784199,29.8015838 C137.958931,37.3510206 135.784352,42.5567762 132.260463,45.4188507 C128.736573,48.2809251 112.33867,64.5239941 83.0667527,94.1480575 L56.2750821,94.1480575 L6.71554594,44.4188507 C2.46876683,39.9813776 0.345377275,35.1089553 0.345377275,29.8015838 C0.345377275,24.4942122 0.230251516,14.560351 -5.68434189e-14,2.84217094e-14 Z" style="fill:currentColor"></path>
                                        <path id="Path1" d="M69.3453773,32.2519224 L101.428699,1.42108547e-14 L138.784583,1.42108547e-14 L138.784199,29.8015838 C137.958931,37.3510206 135.784352,42.5567762 132.260463,45.4188507 C128.736573,48.2809251 112.33867,64.5239941 83.0667527,94.1480575 L56.2750821,94.1480575 L32.8435758,70.5039241 L69.3453773,32.2519224 Z" fill="url(#linearGradient-1)" opacity="0.2"></path>
                                        <polygon id="Path-2" fill="#000000" opacity="0.049999997" points="69.3922914 32.4202615 32.8435758 70.5039241 54.0490008 16.1851325"></polygon>
                                        <polygon id="Path-21" fill="#000000" opacity="0.099999994" points="69.3922914 32.4202615 32.8435758 70.5039241 58.3683556 20.7402338"></polygon>
                                        <polygon id="Path-3" fill="url(#linearGradient-2)" opacity="0.099999994" points="101.428699 0 83.0667527 94.1480575 130.378721 47.0740288"></polygon>
                                    </g>
                                </g>
                            </g>
                        </span>
                        <h2 class="brand-text">{{ env('APP_NAME') }}</h2>
                    </a></li>
                <li class="nav-item nav-toggle"><a class="nav-link modern-nav-toggle pe-0" data-bs-toggle="collapse"><i class="d-block d-xl-none text-primary toggle-icon font-medium-4" data-feather="x"></i><i class="d-none d-xl-block collapse-toggle-icon font-medium-4  text-primary" data-feather="disc" data-ticon="disc"></i></a></li>
            </ul>
        </div>
        <div class="shadow-bottom"></div>
        <div class="main-menu-content">
        </div>
    </div>
    <!-- END: Main Menu-->

<!-- BEGIN: Content-->
<div class="app-content content ">
    <div class="content-overlay"></div>
    <div class="header-navbar-shadow"></div>
    <div class="content-wrapper container-xxl p-0">
        <div class="content-header row">
        <h4>{{ $device }} -> Server: {{$nodeurl}}</h4>
        </div>
        <div class="content-body">
            <!-- devices show start -->
            <section class="app-device-show">
                <!-- show and filter start -->
                <!-- <div class="card"> -->
                    <div class="row">
                        <div class="col-xl-4 col-lg-5 col-md-5 order-1 order-md-0">
                            <div class="card border-primary align-items-center">
                                <div class="device-avatar-section" id="cardimg-{{$device}}">
                                </div>
                            </div>
                            <div class="card-body">
                                <button type="button" class="btn btn-primary" onclick="socksession('{{$device}}', '{{$appurl}}', 'refresh')">{{ __('Refresh') }}</button>
                            </div>
                        </div>
                        <div class="col-xl-8 col-lg-7 col-md-7 order-0 order-md-1">
                            <div class="card border-primary">
                                <textarea class="form-control" id="cardcons-{{$device}}" rows="18" style="background-color: #000;color: #00ff00;border: 1px solid #000;padding: 8px;font-family: courier new;" readonly></textarea>
                            </div>
                        </div>
                    </div>
                <!-- </div> -->
                <!-- show and filter end -->
            </section>
            <!-- devices show ends -->
        </div>
    </div>
</div>
<!-- END: Content-->

<div class="sidenav-overlay"></div>
<div class="drag-target"></div>

<!-- BEGIN: Vendor JS-->
<script src="{{str_replace('/public','',url('/Modules/Wazone'))}}/app-assets/vendors/js/vendors.min.js"></script>
<!-- BEGIN Vendor JS-->

<!-- BEGIN: Page Vendor JS-->
<script src="{{str_replace('/public','',url('/Modules/Wazone'))}}/app-assets/vendors/js/jquery/jquery.min.js"></script>
<script src="{{str_replace('/public','',url('/Modules/Wazone'))}}/app-assets/vendors/js/socket.io/socket.io.js"></script>
<script src="{{str_replace('/public','',url('/Modules/Wazone'))}}/app-assets/vendors/js/clipboard/clipboard.min.js"></script>
<!-- END: Page Vendor JS-->

<!-- BEGIN: Theme JS-->
<script src="{{str_replace('/public','',url('/Modules/Wazone'))}}/app-assets/js/core/app-menu.js"></script>
<script src="{{str_replace('/public','',url('/Modules/Wazone'))}}/app-assets/js/core/app.js"></script>
<!-- END: Theme JS-->

<!-- BEGIN: Page JS-->
<script src="{{str_replace('/public','',url('/Modules/Wazone'))}}/app-assets/js/scripts/components/components-alerts.js"></script>
<!-- END: Page JS-->

<script>
    $(window).on('load', function() {
        if (feather) {
            feather.replace({
                width: 14,
                height: 14
            });
        }
    })
</script>

<script>
    var btns = document.querySelectorAll('button')
    var clipboard = new ClipboardJS(btns)
</script>

<script>
    $(document).ready(function(){
        $('[data-toggle="tooltip"]').tooltip();
    });
</script>

<script>
const socket = io(`{{ $nodeurl }}`)

$(`#cardimg-{{ $device }}`).html(`<img src="{{str_replace('/public','',url('/Modules/Wazone'))}}/app-assets/images/app/loading.gif" class="card-img-top center" alt="loading..." id="qrcode" style="height:250px; width:250px;"><br><p> {{ __('Connecting...') }} </p>`)
socket.emit('socksession', { sender: {{ $device }}, appurl: '{{ $appurl }}', job: 'refresh' })

function socksession(sender, appurl, job = null) {
    $(`#cardimg-${sender}`).html(`<img src="{{str_replace('/public','',url('/Modules/Wazone'))}}/app-assets/images/app/loading.gif" class="card-img-top center" alt="loading..." id="qrcode" style="height:250px; width:250px;"><br><p> {{ __('Connecting...') }} </p>`)
    socket.emit('socksession', { sender: sender, appurl: appurl, job: job })
}

socket.on('refreshCard', (data) => {
    sender = data.sender;
    if (data.status == 'open') {
        imgurl = data.imgurl;
        footer1 = `Name : ${data.name}`;
        footer2 = `WA # : ${data.sender}`;
    } else if (data.status == 'close') {
        imgurl = 'app-assets/images/app/refresh.png';
        footer1 = 'Whatsapp disconnected';
        footer2 = 'Please refresh!';
    } else if (data.status == 'qrReceived') {
        imgurl = data.imgurl;
        footer1 = 'Multi Device (Beta)';
        footer2 = 'Use your phone to scan!';
    }
    $(`#cardimg-${sender}`).html(`
        <img class="img-fluid rounded mt-3 mb-2" src="${imgurl}" height="250" width="250" alt="Device avatar" />
        <ul class="ps-1 mb-2">
            <li class="mb-50">${footer1}</li>
            <li class="mb-50">${footer2}</li>
        </ul>
    `)
})

//Listen on new_message
socket.on("showLog", (data) => {
    let tnow = new Date()
    $(`#cardcons-${data.sender}`).append("\n" + tnow + "\n" + data.text + "\n")
    var pscons = $(`#cardcons-${data.sender}`);
    if(pscons.length)
       pscons.scrollTop(pscons[0].scrollHeight - pscons.height());
})

</script>

    <!-- BEGIN: Footer-->
    <footer class="footer footer-static footer-light">
        <p class="clearfix mb-0"><span class="float-md-start d-block d-md-inline-block mt-25">COPYRIGHT &copy; <script>document.write(new Date().getFullYear());</script> {{ env('APP_NAME') }} by visimisi.net. <span class="d-none d-sm-inline-block"> All rights Reserved</span></span><span class="float-md-end d-none d-md-block">Laravel v{{ Illuminate\Foundation\Application::VERSION }} || PHP v{{ PHP_VERSION }} <i data-feather="heart"></i></span></p>
    </footer>
    <button class="btn btn-primary btn-icon scroll-top" type="button"><i data-feather="arrow-up"></i></button>
    <!-- END: Footer-->

</body>
<!-- END: Body-->

</html>