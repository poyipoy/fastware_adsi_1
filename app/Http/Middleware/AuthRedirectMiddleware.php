<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class AuthRedirectMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Cek apakah pengguna telah terotentikasi
            if (!Auth::check()) {
                // Jika belum terotentikasi, arahkan ke halaman login
                return redirect()->route('login'); // Ubah dari '/login' menjadi 'login'
            }

            // Lanjutkan ke tujuan berikutnya jika pengguna telah terotentikasi
            return $next($request);
    }
}
