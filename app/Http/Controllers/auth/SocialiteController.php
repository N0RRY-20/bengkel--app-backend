<?php

namespace App\Http\Controllers\auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    /**
     * Redirect to Google OAuth.
     */


    public function redirect()
    {
        /** @var \Laravel\Socialite\Two\GoogleProvider $driver */
        return Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * Handle Google OAuth callback.
     */
    public function callback()
    {
        try {
            /** @var \Laravel\Socialite\Two\GoogleUser $googleUser */
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            return redirect(config('app.frontend_url') . '/login?error=google_auth_failed');
        }

        // CASE 1: User sudah pernah login Google sebelumnya
        $user = User::where('google_id', $googleUser->id)->first();
        if ($user) {
            return $this->loginWithToken($user);
        }

        // CASE 2: User punya akun manual dengan email yang sama
        $existingUser = User::where('email', $googleUser->email)->first();
        if ($existingUser) {
            $existingUser->update([
                'google_id' => $googleUser->id,
                'email_verified_at' => now(),
            ]);

            return $this->loginWithToken($existingUser);
        }

        // CASE 3: User benar-benar baru
        $newUser = User::create([
            'google_id' => $googleUser->id,
            'name' => $googleUser->name,
            'email' => $googleUser->email,
            'password' => Hash::make(Str::random(24)),
            'email_verified_at' => now(),
            'role' => 'kasir',
        ]);

        return $this->loginWithToken($newUser);
    }

    /**
     * Generate token and redirect to frontend with auth data.
     */
    private function loginWithToken(User $user)
    {
        // Hapus token lama
        $user->tokens()->delete();

        // Buat token baru
        $tokenName = config('sanctum.token_name', 'auth_token');
        $token = $user->createToken($tokenName)->plainTextToken;

        // Get user role
        $role = $this->getUserRole($user);

        // Redirect ke frontend dengan token dan user data
        $frontendUrl = config('app.frontend_url');
        $redirectPath = match($role) {
            'owner' => '/owner/dashboard',
            'admin' => '/admin/dashboard',
            'kasir' => '/kasir/dashboard',
            'mekanik' => '/mekanik/dashboard',
            default => '/user/dashboard',
        };

        // Encode user data untuk dikirim via URL
        $userData = base64_encode(json_encode([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $role,
            'email_verified_at' => $user->email_verified_at,
        ]));

        return redirect("{$frontendUrl}/auth/callback?token={$token}&user={$userData}&redirect={$redirectPath}");
    }

    private function getUserRole(User $user): string
    {
        return $user->role;
    }
}
