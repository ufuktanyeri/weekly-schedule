@extends('layouts.app')

@section('title', 'Panel')

@section('content')
    <h1 class="mb-1 text-2xl font-semibold">Panel</h1>
    <p class="mb-6 text-sm text-slate-500">
        Aktif dönem: <strong>{{ $donem?->ad() ?? 'Tanımlı değil' }}</strong>
    </p>

    <div class="mb-8 grid grid-cols-2 gap-4 md:grid-cols-5">
        @foreach ($istatistikler as $etiket => $deger)
            <div class="rounded-lg bg-white p-4 shadow">
                <div class="text-xs uppercase text-slate-500">{{ $etiket }}</div>
                <div class="text-2xl font-semibold">{{ $deger }}</div>
            </div>
        @endforeach
    </div>

    <section class="rounded-lg bg-white p-4 shadow">
        <h2 class="mb-3 font-semibold">Haftalık Ders Yükleri</h2>
        @include('partials.workload')
    </section>
@endsection
