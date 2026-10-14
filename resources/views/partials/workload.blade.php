<table class="w-full text-sm">
    <thead class="text-left text-slate-500">
        <tr><th class="py-2">Öğretim Elemanı</th><th>Atanan</th><th>Programlanan</th><th>Sınır</th><th class="w-1/3">Doluluk</th></tr>
    </thead>
    <tbody class="divide-y">
        @forelse ($isYukleri as $satir)
            @php($oran = $satir['limit'] > 0 ? min(100, (int) round($satir['atanan_saat'] / $satir['limit'] * 100)) : 0)
            <tr>
                <td class="py-2">
                    <span class="desen mr-2 inline-block h-3 w-3 rounded-sm align-middle"
                          style="background-color: {{ $satir['ogretim_elemani']->guvenliRenk() }}; background-image: {{ $satir['ogretim_elemani']->desen->css() }};"></span>
                    {{ $satir['ogretim_elemani']->tamAd() }}
                </td>
                <td>{{ $satir['atanan_saat'] }} sa</td>
                <td>{{ $satir['programlanan_saat'] }} sa</td>
                <td>{{ $satir['limit'] }} sa</td>
                <td>
                    <div class="h-2 rounded bg-slate-200">
                        <div class="h-2 rounded {{ $oran >= 100 ? 'bg-red-500' : ($oran >= 80 ? 'bg-amber-500' : 'bg-green-500') }}" style="width: {{ $oran }}%"></div>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="py-4 text-center text-slate-400">Kayıt yok.</td></tr>
        @endforelse
    </tbody>
</table>
