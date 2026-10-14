<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Exceptions\AuthenticationFailedException;
use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Support\GoogleUserData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class GoogleController extends Controller
{
    public function __construct(private readonly AuthService $auth) {}

    public function redirect(): SymfonyRedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException) {
            return redirect()->route('login')->withErrors(['email' => 'Google oturumu doğrulanamadı, lütfen tekrar deneyin.']);
        }

        if (! $googleUser instanceof SocialiteUser || ! is_string($googleUser->getEmail())) {
            return redirect()->route('login')->withErrors(['email' => 'Google hesabından e-posta bilgisi alınamadı.']);
        }

        /** @var array<string, mixed> $raw */
        $raw = $googleUser->getRaw();

        try {
            $user = $this->auth->loginWithGoogle(new GoogleUserData(
                id: (string) $googleUser->getId(),
                email: $googleUser->getEmail(),
                name: (string) ($googleUser->getName() ?? $googleUser->getEmail()),
                emailVerified: ($raw['email_verified'] ?? $raw['verified_email'] ?? false) === true,
                avatarUrl: $googleUser->getAvatar(),
            ));
        } catch (AuthenticationFailedException $e) {
            return redirect()->route('login')->withErrors(['email' => $e->getMessage()]);
        }

        if (! $user->isApproved()) {
            return redirect()->route('login')->with('status', 'Kaydınız alındı. Hesabınız yönetici onayından sonra aktifleşecektir.');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
