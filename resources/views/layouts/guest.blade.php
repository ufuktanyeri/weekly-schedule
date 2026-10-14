@extends('layouts.base')

@section('body')
    <main class="flex min-h-screen items-center justify-center px-4">
        <div class="w-full max-w-md rounded-lg bg-white p-8 shadow">
            <h1 class="mb-6 text-center text-xl font-semibold text-brand-700">Haftalık Ders Programı</h1>
            @include('partials.flash')
            @yield('content')
        </div>
    </main>
@endsection
