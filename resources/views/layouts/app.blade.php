@extends('layouts.base')

@section('body')
    <nav class="bg-brand-700 text-white shadow">
        <div class="mx-auto flex max-w-screen-2xl flex-wrap items-center gap-6 px-4 py-3">
            <a href="{{ route('dashboard') }}" class="text-lg font-semibold">Haftalık Ders Programı</a>
            <div class="flex flex-wrap gap-4 text-sm">
                <a href="{{ route('dashboard') }}" class="hover:underline">Panel</a>
                <a href="{{ route('schedule.index') }}" class="hover:underline">Program</a>
                @can('manage-schedule')
                    <a href="{{ route('assignments.index') }}" class="hover:underline">Ders Atamaları</a>
                    <a href="{{ route('conflicts.index') }}" class="hover:underline">Çakışma Kayıtları</a>
                @endcan
                @can('manage-users')
                    <a href="{{ route('approvals.index') }}" class="hover:underline">Kullanıcı Onayları</a>
                @endcan
            </div>
            <div class="ml-auto flex items-center gap-3 text-sm">
                <span>{{ auth()->user()?->name }} <span class="rounded bg-white/20 px-2 py-0.5 text-xs">{{ auth()->user()?->role->label() }}</span></span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="rounded bg-white/10 px-3 py-1 hover:bg-white/20">Çıkış</button>
                </form>
            </div>
        </div>
    </nav>

    <main class="mx-auto max-w-screen-2xl px-4 py-6">
        @include('partials.flash')
        @yield('content')
    </main>
@endsection
