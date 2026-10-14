@extends('layouts.guest')

@section('title', 'Kayıt')

@section('content')
    <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
        @csrf
        <div>
            <label for="name" class="block text-sm font-medium">Ad Soyad</label>
            <input id="name" name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded px-3 py-2 ring-1 ring-slate-300">
        </div>
        <div>
            <label for="email" class="block text-sm font-medium">E-posta</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required class="mt-1 w-full rounded px-3 py-2 ring-1 ring-slate-300">
        </div>
        <div>
            <label for="password" class="block text-sm font-medium">Şifre</label>
            <input id="password" name="password" type="password" required class="mt-1 w-full rounded px-3 py-2 ring-1 ring-slate-300">
        </div>
        <div>
            <label for="password_confirmation" class="block text-sm font-medium">Şifre (tekrar)</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required class="mt-1 w-full rounded px-3 py-2 ring-1 ring-slate-300">
        </div>
        <div>
            <label for="requested_role" class="block text-sm font-medium">Talep edilen rol</label>
            <select id="requested_role" name="requested_role" class="mt-1 w-full rounded px-3 py-2 ring-1 ring-slate-300">
                @foreach ($roles as $role)
                    <option value="{{ $role->value }}" @selected(old('requested_role', 'guest') === $role->value)>{{ $role->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="note" class="block text-sm font-medium">Not (isteğe bağlı)</label>
            <textarea id="note" name="note" rows="2" class="mt-1 w-full rounded px-3 py-2 ring-1 ring-slate-300">{{ old('note') }}</textarea>
        </div>
        <button class="w-full rounded bg-brand-600 px-4 py-2 font-medium text-white hover:bg-brand-700">Kayıt Ol</button>
        <p class="text-xs text-slate-500">Yeni hesaplar yönetici onayına kadar <strong>beklemede</strong> kalır.</p>
    </form>
    <p class="mt-6 text-center text-sm"><a href="{{ route('login') }}" class="text-brand-600 hover:underline">Girişe dön</a></p>
@endsection
