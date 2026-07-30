<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{config('app.name')}} - {{$title ?? ''}}</title>
    @vite(['resources/css/app.css'])
    @livewireStyles
    {{$style ?? ''}}
</head>
<body class="d-flex flex-column min-vh-100">
    <x-layouts._nav/>
    @if(session('errors'))
    <div class="alert alert-danger" role="alert">{{session('errors')}}</div>
    @endif
    @if(session('message'))
    <div class="alert alert-success" role="alert">{{session('message')}}</div>
    @endif
    <main class="flex-grow-1">
      {{$slot}}
    </main>
    <footer class="bg-dark text-white mt-5 py-4 border-top shadow-lg position-relative z-2">
        <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between">
            <div class="mb-3 mb-md-0">
                <span class="fw-bold">CyberBlog</span> &copy; {{ date('Y') }}. All rights reserved.
            </div>
            <div>
                <a href="#" class="text-white me-3 fs-5" title="Twitter"><i class="bi bi-twitter"></i></a>
                <a href="#" class="text-white me-3 fs-5" title="GitHub"><i class="bi bi-github"></i></a>
                <a href="#" class="text-white fs-5" title="LinkedIn"><i class="bi bi-linkedin"></i></a>
            </div>
        </div>
    </footer>
    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    @vite(['resources/js/app.js'])
    @livewireScripts
    {{$scripts ?? ''}}
</body>
</html>
