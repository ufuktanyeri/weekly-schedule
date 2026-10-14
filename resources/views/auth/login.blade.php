@extends('layouts.guest')

@section('title', 'Giriş')

@section('content')
    <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
        @csrf
        <div>
            <label for="email" class="block text-sm font-medium">E-posta</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                   class="mt-1 w-full rounded border-slate-300 px-3 py-2 shadow-sm ring-1 ring-slate-300">
        </div>
        <div>
            <label for="password" class="block text-sm font-medium">Şifre</label>
            <input id="password" name="password" type="password" required
                   class="mt-1 w-full rounded border-slate-300 px-3 py-2 shadow-sm ring-1 ring-slate-300">
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="remember" value="1"> Beni hatırla
        </label>
        <button class="w-full rounded bg-brand-600 px-4 py-2 font-medium text-white hover:bg-brand-700">Giriş Yap</button>
    </form>

    <div class="my-6 flex items-center gap-3 text-xs text-slate-400"><span class="h-px flex-1 bg-slate-200"></span>veya<span class="h-px flex-1 bg-slate-200"></span></div>

    <a href="{{ route('auth.google') }}" class="block w-full rounded border border-slate-300 px-4 py-2 text-center font-medium hover:bg-slate-50">
        Google ile Giriş Yap
    </a>

    <p class="mt-6 text-center text-sm">Hesabınız yok mu? <a href="{{ route('register') }}" class="text-brand-600 hover:underline">Kayıt olun</a></p>
@endsection
