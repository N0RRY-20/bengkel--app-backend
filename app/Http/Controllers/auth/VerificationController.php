<?php

namespace App\Http\Controllers\auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    /**
     * Verify email with signed URL.
     * User mengklik link dari email, tidak perlu auth karena link sudah signed.
     */
    public function verify(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        // Verify the hash matches
        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return redirect(config('app.frontend_url') . '/login?error=invalid_link');
        }

        // Check if already verified
        if ($user->hasVerifiedEmail()) {
            return redirect(config('app.frontend_url') . '/email-verified?already=true');
        }

        // Mark as verified
        $user->markEmailAsVerified();
        event(new Verified($user));

        // Redirect ke frontend setelah verifikasi berhasil
        return redirect(config('app.frontend_url') . '/email-verified');
    }

    /**
     * Resend verification email.
     * Memerlukan auth dengan token.
     */
    public function resend(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email sudah diverifikasi sebelumnya.'
            ], 400);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Link verifikasi telah dikirim ulang ke email Anda!'
        ]);
    }

    /**
     * Check verification status (API endpoint).
     */
    public function status(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'email_verified' => $user->hasVerifiedEmail(),
            'email_verified_at' => $user->email_verified_at,
        ]);
    }
}
