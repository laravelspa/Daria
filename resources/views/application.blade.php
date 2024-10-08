<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'REPAIR') }}</title>
    <link rel="icon" type="image/x-icon" href="/assets/favicon/favicon.ico">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .loader {
            --speed: 1000ms;
            position: relative;
            font-size: 2.5em;
        }

        .loader .tile {
            width: 4em;
            height: 4em;
            animation: var(--speed) ease infinite jump;
            transform-origin: 0 100%;
            will-change: transform;
        }

        .loader .tile::before {
            content: "";
            display: block;
            width: inherit;
            height: inherit;
            border-radius: 0.15em;
            background-color: #6C2EF2;
            animation: var(--speed) ease-out infinite spin;
            will-change: transform;
        }

        .loader::after {
            content: "";
            display: block;
            width: 1.1em;
            height: 0.2em;
            background-color: #0132;
            border-radius: 60%;
            position: absolute;
            left: -0.05em;
            bottom: -0.1em;
            z-index: -1;
            animation: var(--speed) ease-in-out infinite shadow;
            filter: blur(0.02em);
            will-change: transform filter;
        }

        @keyframes jump {
            0% {
                transform: scaleY(1) translateY(0);
            }

            16% {
                transform: scaleY(0.6) translateY(0);
            }

            22% {
                transform: scaleY(1.2) translateY(-5%);
            }

            24%,
            62% {
                transform: scaleY(1) translateY(-33%);
            }

            66% {
                transform: scaleY(1.2) translateY(0);
            }

            72% {
                transform: scaleY(0.8) translateY(0);
            }

            88% {
                transform: scaleY(1) translateY(0);
            }
        }

        @keyframes spin {

            0%,
            18% {
                transform: rotateZ(0);
                border-radius: 0.15em;
            }

            38% {
                border-radius: 0.25em;
            }

            66%,
            100% {
                transform: rotateZ(1turn);
                border-radius: 0.15em;
            }
        }

        @keyframes shadow {
            0% {
                transform: scale(1);
                filter: blur(0.02em);
            }

            25%,
            60% {
                transform: scale(0.8);
                filter: blur(0.06em);
            }

            70% {
                transform: scale(1.1);
                filter: blur(0.02em);
            }

            90% {
                transform: scale(1);
            }
        }

        // Demo

        :root {
            box-sizing: border-box;
            height: 100%;
            -webkit-font-smoothing: antialiased;
            background-color: var(--color-root, hsl(210 18% 44%));
            color: var(--color-text, hsl(210 15% 96%));
            background-image: radial-gradient(#fff4, #fff0);
        }

        *,
        :before,
        :after {
            box-sizing: inherit;
        }

        #wrapper {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            line-height: 1.375;
        }
    </style>
</head>

<body>

    <div id="app">
        <div id="wrapper">
            <div class="loader">
                <div class="tile"></div>
            </div>
        </div>
    </div>
</body>

</html>
