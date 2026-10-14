@extends('layouts.app')

@section('title', 'Ders Atamaları')

@section('content')
    <div class="mb-4 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Ders Atamaları</h1>
            <p class="text-sm text-slate-500">{{ $donem?->ad() ?? 'Dönem seçilmedi' }}</p>
        </div>
        <form method="GET" class="text-sm">
            <select name="donem" onchange="this.form.submit()" class="rounded px-2 py-1 ring-1 ring-slate-300">
                @foreach ($donemler as $d)
                    <option value="{{ $d->id }}" @selected($donem?->id === $d->id)>{{ $d->ad() }}</option>
                @endforeach
            </select>
        </form>
    </div>

    @if ($donem)
        <form method="POST" action="{{ route('assignments.store') }}" class="mb-6 grid grid-cols-2 gap-2 rounded-lg bg-white p-4 text-sm shadow md:grid-cols-6">
            @csrf
            <input type="hidden" name="akademik_donem_id" value="{{ $donem->id }}">
            <select name="ders_id" required class="col-span-2 rounded px-2 py-1 ring-1 ring-slate-300">
                <option value="">Ders seçin</option>
                @foreach ($dersler as $ders)
                    <option value="{{ $ders->id }}" @selected((int) old('ders_id') === $ders->id)>
                        {{ $ders->ders_kodu }} {{ $ders->ders_adi }} ({{ $ders->haftalikSaat() }} sa) — {{ $ders->program->program_kodu }}
                    </option>
                @endforeach
            </select>
            <select name="ogretim_elemani_id" required class="col-span-2 rounded px-2 py-1 ring-1 ring-slate-300">
                <option value="">Öğretim elemanı seçin</option>
                @foreach ($ogretimElemanlari as $oe)
                    <option value="{{ $oe->id }}" @selected((int) old('ogretim_elemani_id') === $oe->id)>{{ $oe->tamAd() }}</option>
                @endforeach
            </select>
            <div class="flex gap-1">
                <input name="haftalik_saat" type="number" min="1" max="40" value="{{ old('haftalik_saat') }}" placeholder="Saat" class="w-full rounded px-2 py-1 ring-1 ring-slate-300">
                <input name="sube" value="{{ old('sube', 'A') }}" maxlength="10" placeholder="Şube" class="w-16 rounded px-2 py-1 ring-1 ring-slate-300">
            </div>
            <button class="rounded bg-green-600 px-3 py-1.5 text-white hover:bg-green-700">Ata</button>
        </form>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-lg bg-white p-4 shadow">
            <h2 class="mb-3 font-semibold">Atamalar</h2>
            <table class="w-full text-sm">
                <thead class="text-left text-slate-500"><tr><th class="py-2">Ders</th><th>Şube</th><th>Öğretim Elemanı</th><th>Saat</th><th></th></tr></thead>
                <tbody class="divide-y">
                    @forelse ($atamalar as $a)
                        <tr>
                            <td class="py-2">{{ $a->ders->ders_kodu }} {{ $a->ders->ders_adi }}</td>
                            <td>{{ $a->sube }}</td>
                            <td>{{ $a->ogretimElemani->tamAd() }}</td>
                            <td>{{ $a->haftalik_saat }}</td>
                            <td class="text-right">
                                <form method="POST" action="{{ route('assignments.destroy', $a) }}" onsubmit="return confirm('Atama ve bağlı program kayıtları silinsin mi?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-red-600 hover:underline">Sil</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-4 text-center text-slate-400">Atama yok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section class="rounded-lg bg-white p-4 shadow">
            <h2 class="mb-3 font-semibold">Haftalık Ders Yükleri</h2>
            @include('partials.workload')
        </section>
    </div>
@endsection
