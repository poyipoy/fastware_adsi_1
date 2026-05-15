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

        \Illuminate\Support\Facades\Log::info('LOGIN ATTEMPT', $request->all());

        // Coba autentikasi menggunakan 'username' dan 'password'
        // Pastikan kolom 'username' ada di tabel 'users' Anda
        if (!Auth::attempt($request->only('username', 'password'))) {
            throw ValidationException::withMessages([
                'username' => [trans('auth.failed')], // Pesan error untuk 'username'
            ]);
        }

        $user = $request->user();
        $token = $user->createToken('auth-token')->plainTextToken;

        // UPDATE FCM TOKEN IF PROVIDED DURING LOGIN
        if ($request->filled('fcm_token')) {
            $user->fcm_token = $request->input('fcm_token');
            $user->save();
        }

        // Definisikan pengguna sales dan depthead secara manual
        $salesUsers = array_map('strtoupper', [
            'DANIA ISNAWATI',
            'FISKA CHRISMAS YUDHA',
            'DWI KUNTORO',
            'YUNASIS PALGUNADI',
            'HEXAPA DARMADI',
            'HERY HERMAWAN',
            'RIFQI RAHMAT DZATNIKA',
            'SARAH EGA BUDI ASTUTI',
            'DIMAS ADITYA PRIANDANA',
            'SONY STIAWAN',
            'YAN WELEM MANGINSELA',
            'WULYO EKO PRASETYO',
            'SENDY PRABOWO',
        ]);

        $deptHeadUsers = array_map('strtoupper', [
            'ANDIK TOTOK SISWOYO',
            'ILHAM CHOLID',
            'JUN JOHAMIN PD',
            'YULMAI RIDO WINANDA',
            'NANI SUTARMAN',
            'HARDI SAPUTRA',
        ]);

        $adminUsers = array_map('strtoupper', [
            'ADMINISTRATOR',
            'ADMINSTRATOR',
        ]);

        $role = 'unknown'; // Peran default
        $username = strtoupper($user->name);
        if (in_array($username, $salesUsers, true)) {
            $role = 'sales';
        } elseif (in_array($username, $deptHeadUsers, true)) {
            $role = 'depthead';
        } elseif (in_array($username, $adminUsers, true)) {
            $role = 'admin';
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

    }

    /**
     * Update FCM Token for the authenticated user.
     */
    public function updateFcmToken(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('FCM UPDATE HIT', $request->all());

        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $user = $request->user();
        
        // Simpan token ke kolom fcm_token (pastikan sudah migrate)
        $user->fcm_token = $request->input('fcm_token');
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'FCM Token updated successfully',
        ]);
    }
}
