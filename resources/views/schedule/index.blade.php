@extends('layouts.app')

@section('title', 'Haftalık Program')

@section('content')
    <div class="mb-4 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Haftalık Program</h1>
            <p class="text-sm text-slate-500">{{ $donem?->ad() ?? 'Dönem seçilmedi' }}</p>
        </div>

        <form method="GET" action="{{ route('schedule.index') }}" class="flex flex-wrap items-end gap-2 text-sm">
            <label>Dönem
                <select name="donem" class="block rounded px-2 py-1 ring-1 ring-slate-300">
                    @foreach ($donemler as $d)
                        <option value="{{ $d->id }}" @selected($donem?->id === $d->id)>{{ $d->ad() }}</option>
                    @endforeach
                </select>
            </label>
            <label>Program
                <select name="program" class="block rounded px-2 py-1 ring-1 ring-slate-300">
                    <option value="">Tümü</option>
                    @foreach ($programlar as $p)
                        <option value="{{ $p->id }}" @selected($filtre['program'] === $p->id)>{{ $p->program_adi }}</option>
                    @endforeach
                </select>
            </label>
            <label>Öğretim Elemanı
                <select name="ogretim_elemani" class="block rounded px-2 py-1 ring-1 ring-slate-300">
                    <option value="">Tümü</option>
                    @foreach ($ogretimElemanlari as $oe)
                        <option value="{{ $oe->id }}" @selected($filtre['ogretim_elemani'] === $oe->id)>{{ $oe->tamAd() }}</option>
                    @endforeach
                </select>
            </label>
            <label>Derslik
                <select name="derslik" class="block rounded px-2 py-1 ring-1 ring-slate-300">
                    <option value="">Tümü</option>
                    @foreach ($derslikler as $dl)
                        <option value="{{ $dl->id }}" @selected($filtre['derslik'] === $dl->id)>{{ $dl->derslik_kodu }}</option>
                    @endforeach
                </select>
            </label>
            <button class="rounded bg-brand-600 px-3 py-1.5 text-white">Filtrele</button>
        </form>
    </div>

    @can('manage-schedule')
        @if ($donem)
            <form method="POST" action="{{ route('schedule.store') }}" class="mb-6 grid grid-cols-2 gap-2 rounded-lg bg-white p-4 text-sm shadow md:grid-cols-6">
                @csrf
                <select name="ders_atama_id" required class="col-span-2 rounded px-2 py-1 ring-1 ring-slate-300">
                    <option value="">Ders ataması seçin</option>
                    @foreach ($atamalar as $a)
                        <option value="{{ $a->id }}" @selected((int) old('ders_atama_id') === $a->id)>
                            {{ $a->ders->ders_kodu }} {{ $a->ders->ders_adi }} ({{ $a->sube }}) — {{ $a->ogretimElemani->tamAd() }}
                        </option>
                    @endforeach
                </select>
                <select name="derslik_id" required class="rounded px-2 py-1 ring-1 ring-slate-300">
                    @foreach ($derslikler as $dl)
                        <option value="{{ $dl->id }}" @selected((int) old('derslik_id') === $dl->id)>{{ $dl->derslik_kodu }} ({{ $dl->kapasite }})</option>
                    @endforeach
                </select>
                <select name="gun" required class="rounded px-2 py-1 ring-1 ring-slate-300">
                    @foreach ($gunler as $g)
                        <option value="{{ $g->value }}" @selected((int) old('gun') === $g->value)>{{ $g->label() }}</option>
                    @endforeach
                </select>
                <div class="flex gap-1">
                    <select name="baslangic_saati" required class="w-full rounded px-2 py-1 ring-1 ring-slate-300">
                        @foreach ($slotlar as $s)
                            <option value="{{ $s->start }}" @selected(old('baslangic_saati') === $s->start)>{{ $s->start }}</option>
                        @endforeach
                    </select>
                    <select name="bitis_saati" required class="w-full rounded px-2 py-1 ring-1 ring-slate-300">
                        @foreach ($slotlar as $s)
                            <option value="{{ $s->end }}" @selected(old('bitis_saati') === $s->end)>{{ $s->end }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="rounded bg-green-600 px-3 py-1.5 text-white hover:bg-green-700">Programa Ekle</button>
            </form>
        @endif
    @endcan

    <div class="overflow-x-auto rounded-lg bg-white shadow">
        <table class="w-full table-fixed border-collapse text-xs">
            <thead>
                <tr class="bg-slate-50 text-slate-600">
                    <th class="w-28 border p-2">Saat</th>
                    @foreach ($gunler as $g)
                        <th class="border p-2">{{ $g->label() }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($slotlar as $slot)
                    @php($ogle = $timeSlots->isLunch($slot))
                    <tr class="{{ $ogle ? 'bg-slate-100 text-slate-400' : '' }}">
                        <td class="border p-1 text-center font-mono">{{ $slot->label() }}</td>
                        @foreach ($gunler as $g)
                            <td class="h-10 border p-0.5 align-top">
                                @if ($ogle)
                                    <div class="text-center italic">Öğle arası</div>
                                @endif
                                @foreach ($grid[$g->value][$slot->start] ?? [] as $kayit)
                                    @php($oe = $kayit->ogretimElemani)
                                    <div class="ders-hucre desen mb-0.5 flex items-start justify-between gap-1 rounded px-1 py-0.5 text-white"
                                         style="background-color: {{ $oe->guvenliRenk() }}; background-image: {{ $oe->desen->css() }};"
                                         title="{{ $kayit->dersAtama->ders->ders_adi }} · {{ $oe->tamAd() }} · {{ $kayit->derslik->derslik_adi }}">
                                        <span class="truncate">
                                            <strong>{{ $kayit->dersAtama->ders->ders_kodu }}</strong>
                                            {{ $kayit->derslik->derslik_kodu }}<br>
                                            <span class="opacity-90">{{ $oe->ad }} {{ $oe->soyad }}</span>
                                        </span>
                                        @can('manage-schedule')
                                            <form method="POST" action="{{ route('schedule.destroy', $kayit) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="leading-none" title="Sil" aria-label="Sil">×</button>
                                            </form>
                                        @endcan
                                    </div>
                                @endforeach
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
