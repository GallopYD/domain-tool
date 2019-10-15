<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', '域名工具') }}</title>
    <meta name="keywords" content="域名工具"/>
    <meta name="description " content="域名工具"/>

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="https://fonts.gstatic.com">
    {{--<link href="https://fonts.googleapis.com/css?family=Raleway:300,400,600" rel="stylesheet" type="text/css">--}}

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body>
<div id="app">
    <nav class="navbar navbar-expand-md navbar-light navbar-laravel">
        <div class="container">
            <a class="navbar-brand" href="{{ url('/') }}">
                域名工具
            </a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent"
                    aria-controls="navbarSupportedContent" aria-expanded="false"
                    aria-label="{{ __('Toggle navigation') }}">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <!-- Left Side Of Navbar -->
                <ul class="navbar-nav mr-auto">

                </ul>
                <!-- Right Side Of Navbar -->
                <ul class="navbar-nav ml-auto">
                    <!-- Authentication Links -->
                    <li class="nav-item">
                        <a class="nav-link @if(!\Illuminate\Support\Facades\Request::route()->type || \Illuminate\Support\Facades\Request::route()->type == 'wechat') active @endif"
                           href="{{route('check',['type'=>'wechat'])}}">微信查询</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link @if(\Illuminate\Support\Facades\Request::route()->type == 'qq') active @endif"
                           href="{{route('check',['type'=>'qq'])}}">QQ查询</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link @if(\Illuminate\Support\Facades\Request::route()->type == '360') active @endif"
                           href="{{route('check',['type'=>'360'])}}">360查询</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link @if(\Illuminate\Support\Facades\Request::route()->type == 'whois') active @endif"
                           href="{{route('check',['type'=>'whois'])}}">Whois</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" target="_blank" href="/api/doc">API</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" target="_blank" href="https://github.com/GallopYD/domain-tool">GitHub<img
                                    class="github-logo" src="{{asset('images/github.jpg')}}"></a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <main class="py-4">
        @yield('content')

        <div class="text-center my-footer">
            友情链接：<a href="https://github.com/GallopYD/proxy-pool">免费代理</a><br>
            <script type="text/javascript"
                    src="https://s23.cnzz.com/z_stat.php?id=1275295247&web_id=1275295247"></script>
        </div>
    </main>
</div>
@yield('js')
</body>
</html>
