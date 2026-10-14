<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class RegisterController extends Controller
{
    public function __construct(private readonly AuthService $auth) {}

    public function create(): View
    {
        return view('auth.register', ['roles' => RegisterRequest::REQUESTABLE_ROLES]);
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $this->auth->register(
            $request->string('name')->toString(),
            $request->string('email')->toString(),
            $request->string('password')->toString(),
            $request->enum('requested_role', Role::class) ?? Role::Guest,
            $request->filled('note') ? $request->string('note')->toString() : null,
        );

        return redirect()->route('login')->with('status', 'Kaydınız alındı. Hesabınız yönetici onayından sonra aktifleşecektir.');
    }
}
