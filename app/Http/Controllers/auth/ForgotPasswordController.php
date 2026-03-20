<?php

namespace App\Http\Controllers\auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    public function store(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if ($user) {
            Password::sendResetLink(
                $request->only('email')
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Kami telah mengirimkan link reset password ke email Anda jika email tersebut terdaftar di sistem kami. Silakan cek email Anda.'
        ]);
    }
}
