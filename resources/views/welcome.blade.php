<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{!! env('APP_NAME') !!} | Cape & Bay </title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet">
        <link rel="stylesheet" href="https://use.typekit.net/jrs3rip.css">

        <!-- Styles -->
        <style>
            html, body {
                background-color: #2D2D2D;
                color: #fff;
                font-family: semplicitapro, sans-serif;
                font-weight: 700;
                font-style: normal;
                height: 100vh;
                margin: 0;
            }

            .full-height {
                height: 100vh;
            }

            .flex-center {
                align-items: center;
                display: flex;
                justify-content: center;
            }

            .position-ref {
                position: relative;
            }

            .top-right {
                position: absolute;
                right: 10px;
                top: 18px;
            }

            .content {
                text-align: center;
            }

            .title {
                font-size: 84px;
            }

            .links > a {
                color: #fff;
                padding: 0 25px;
                font-size: 13px;
                font-weight: 600;
                letter-spacing: .1rem;
                text-decoration: none;
                text-transform: uppercase;
            }

            .links > a:hover {
                color: #DFF200;
            }

            .m-b-md {
                margin-bottom: 30px;
            }

            .img-line {
                display:flex;
                flex-flow: row wrap;
                width: 100%;
                align-items: center;
                justify-content: center;
            }

            .img-line img {
                width: 25%;
                height: 25%;
            }
        </style>
    </head>
    <body>
        <div class="flex-center position-ref full-height">
            @if (Route::has('login'))
                <div class="top-right links">
                    @auth
                        <a href="{{ url('/home') }}">Home</a>
                    @else
                        <a href="{{ route('login') }}">Login</a>

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}">Register</a>
                        @endif
                    @endauth
                </div>
            @endif

            <div class="content">
                <div class="title m-b-md img-line">
                    <img src="{{ asset('/img/icon.png') }}"> <span>x</span> <img src="{{ asset('/img/anchor-icon.png') }}">
                </div>

                <div class="title m-b-md">
                    {!! env('APP_NAME') !!} - {!! env('APP_ENV') !!}
                </div>

                <div class="links">
                    <a href="/about">About</a>
                    <a href="/products">Products</a>
                    <a href="/news">News</a>
                    <a href="/access">Login</a>
                    <a href="https://allcommerce.capeandbay.com">AllCommerce</a>
                    <a href="https://capeandbay.com">Cape & Bay</a>
                    <a href="/developers">API</a>
                    <a href="/shop">Purchase</a>
                </div>
            </div>
        </div>
    </body>
</html>
