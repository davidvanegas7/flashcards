<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased dark:bg-black dark:text-white/50">
        @include('navbar-welcome')
        <div class="bg-gradient-to-b from-[#7267cb] to-[#6e3cbc] mx-auto px-4 min-h-[calc(100vh-60px)] flex flex-col-reverse md:flex-row items-center justify-center gap-4 py-6">
            <div class="w-content-text text-center text-white md:text-left md:w-1/2 space-y-4">
                <h1 class="text-4xl md:text-5xl font-bold mb-6">Aprende de manera facíl y rapida</h1>
                <p class="text-lg md:text-xl mb-4">Con Flash Cards crea tus propias cartas para ti y compartir
                con mas gente tu baraja de cartas.</p>
                <p class="text-lg md:text-xl mb-4">Aprovecha y disfruta la experiencia de usar esta app</p>
                <p class="text-lg md:text-xl mb-6">Registrate y comienza a crear tus propias cartas</p> 
                <a href="#" class="inline-block bg-white text-[#7267cb] border border-blue-600 px-8 py-3 rounded-lg hover:bg-blue-50 transition-colors text-lg">Comienza ahora!</a>
            </div>
            <div class="md:w-2/3">
                <img src="{{ asset('img/telefono.png') }}" alt="Teléfono con flashcards" class="mx-auto"/>
            </div>
        </div>
    </body>
</html>
