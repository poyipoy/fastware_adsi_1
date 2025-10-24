<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;


class AuthController extends Controller
{
    /**
     * Handle an incoming authentication request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {
        // Validasi input: 'username' dan 'password'
        $request->validate([
            'username' => ['required', 'string'], // Ubah dari 'email' menjadi 'username'
            'password' => ['required', 'string'],
        ]);

        // Coba autentikasi menggunakan 'username' dan 'password'
        // Pastikan kolom 'username' ada di tabel 'users' Anda
        if (!Auth::attempt($request->only('username', 'password'))) {
            throw ValidationException::withMessages([
                'username' => [trans('auth.failed')], // Pesan error untuk 'username'
            ]);
        }

        $user = $request->user();
        $token = $user->createToken('auth-token')->plainTextToken;

        // Definisikan pengguna sales dan depthead secara manual
        $salesUsers = ['asep', 'boy'];
        $deptHeadUsers = ['doni', 'eka']; // Contoh untuk depthead

        $role = 'unknown'; // Peran default
        if (in_array($user->username, $salesUsers)) {
            $role = 'sales';
        } elseif (in_array($user->username, $deptHeadUsers)) {
            $role = 'depthead';
        }

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
            'role' => $role, // Tambahkan peran ke respons
        ]);
    }

    /**
     * Destroy an authenticated session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout successful',
        ]);
    }
}
