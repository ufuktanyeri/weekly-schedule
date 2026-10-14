@extends('layouts.app')

@section('title', 'Kullanıcı Onayları')

@section('content')
    <h1 class="mb-4 text-2xl font-semibold">Kullanıcı Onayları</h1>

    <section class="mb-8 rounded-lg bg-white p-4 shadow">
        <h2 class="mb-3 font-semibold">Onay Bekleyenler</h2>
        <table class="w-full text-sm">
            <thead class="text-left text-slate-500">
                <tr><th class="py-2">Ad</th><th>E-posta</th><th>Kaynak</th><th>Talep</th><th>Not</th><th class="w-96">İşlem</th></tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($bekleyenler as $u)
                    @php($talep = $u->approvalRequests->first())
                    <tr>
                        <td class="py-2">{{ $u->name }}</td>
                        <td>{{ $u->email }}</td>
                        <td>{{ $u->auth_provider->value }}</td>
                        <td>{{ $talep?->requested_role->label() ?? '-' }}</td>
                        <td class="max-w-xs truncate">{{ $talep?->note }}</td>
                        <td class="flex flex-wrap gap-2 py-2">
                            <form method="POST" action="{{ route('approvals.approve', $u) }}" class="flex gap-1">
                                @csrf
                                <select name="role" class="rounded px-1 py-1 ring-1 ring-slate-300">
                                    @foreach ($roller as $rol)
                                        @if (auth()->user()?->role->canGrant($rol))
                                            <option value="{{ $rol->value }}" @selected($talep?->requested_role === $rol)>{{ $rol->label() }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                <button class="rounded bg-green-600 px-2 py-1 text-white">Onayla</button>
                            </form>
                            <form method="POST" action="{{ route('approvals.reject', $u) }}" class="flex gap-1">
                                @csrf
                                <input name="reason" placeholder="Gerekçe" maxlength="1000" class="w-28 rounded px-1 py-1 ring-1 ring-slate-300">
                                <button class="rounded bg-red-600 px-2 py-1 text-white">Reddet</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-4 text-center text-slate-400">Onay bekleyen kullanıcı yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="rounded-lg bg-white p-4 shadow">
        <h2 class="mb-3 font-semibold">Son İşlemler</h2>
        <table class="w-full text-sm">
            <thead class="text-left text-slate-500"><tr><th class="py-2">Ad</th><th>E-posta</th><th>Rol</th><th>Durum</th></tr></thead>
            <tbody class="divide-y">
                @foreach ($sonIslemler as $u)
                    <tr>
                        <td class="py-2">{{ $u->name }}</td>
                        <td>{{ $u->email }}</td>
                        <td>{{ $u->role->label() }}</td>
                        <td>{{ $u->status->label() }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
@endsection
