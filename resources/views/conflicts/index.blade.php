@extends('layouts.app')

@section('title', 'Çakışma Kayıtları')

@section('content')
    <h1 class="mb-4 text-2xl font-semibold">Çakışma Kayıtları</h1>

    <div class="overflow-x-auto rounded-lg bg-white p-4 shadow">
        <table class="w-full text-sm">
            <thead class="text-left text-slate-500">
                <tr><th class="py-2">Tarih</th><th>Tür</th><th>Dönem</th><th>Gün / Saat</th><th>Ders</th><th>Öğretim Elemanı</th><th>Derslik</th><th>Mesaj</th><th>Kullanıcı</th></tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($kayitlar as $k)
                    <tr class="align-top">
                        <td class="whitespace-nowrap py-2">{{ $k->created_at?->format('d.m.Y H:i') }}</td>
                        <td><span class="rounded bg-red-100 px-2 py-0.5 text-xs text-red-700">{{ $k->cakisma_turu->label() }}</span></td>
                        <td>{{ $k->akademikDonem?->ad() }}</td>
                        <td class="whitespace-nowrap">{{ $k->gun?->label() }} {{ $k->baslangic_saati }}{{ $k->bitis_saati ? '-'.$k->bitis_saati : '' }}</td>
                        <td>{{ $k->ders?->ders_kodu }}</td>
                        <td>{{ $k->ogretimElemani?->tamAd() }}</td>
                        <td>{{ $k->derslik?->derslik_kodu }}</td>
                        <td>{{ $k->mesaj }}</td>
                        <td>{{ $k->user?->name }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="py-4 text-center text-slate-400">Kayıt yok.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $kayitlar->links() }}</div>
    </div>
@endsection
