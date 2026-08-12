<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\TcPeopleDevelopment;
use App\Services\HR\TrainingEvaluationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function __construct(
        private readonly TrainingEvaluationService $trainingEvaluation,
    ) {
    }

    // showpagelogin
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function showDataDiri()
    {
        $user = Auth::user();
        $role = $user->roles;
        $jobPositions = $user->jobPositions;

        $dataTcPeopleDevelopment = TcPeopleDevelopment::query()
            ->with([
                'user:id,npk,name',
                'participants:id,npk,name',
                'section:id,name',
            ])
            ->where('status_2', 'Done')
            ->where(function (Builder $query) use ($user): void {
                $query
                    ->where(function (Builder $training) use ($user): void {
                        $training
                            ->where(function (Builder $regular): void {
                                $regular
                                    ->where('is_sharing_knowledge', false)
                                    ->orWhereNull('is_sharing_knowledge');
                            })
                            ->where('id_user', $user->id);
                    })
                    ->orWhere(function (Builder $sharing) use ($user): void {
                        $sharing
                            ->where('is_sharing_knowledge', true)
                            ->whereNotNull('dievaluasi')
                            ->where('dievaluasi', '<>', '')
                            ->whereNotNull('tgl_pengajuan')
                            ->where(function (Builder $participant) use ($user): void {
                                $participant
                                    ->whereHas('participants', fn (Builder $users) => $users->whereKey($user->id))
                                    ->orWhere(function (Builder $legacy) use ($user): void {
                                        $legacy
                                            ->whereDoesntHave('participants')
                                            ->where('id_user', $user->id);
                                    });
                            });
                    });
            })
            ->latest('id')
            ->get()
            ->map(fn (TcPeopleDevelopment $training): array => $this->trainingEvaluation->payload($training));

        return view('auth.dataDiri', compact('user', 'role', 'jobPositions', 'dataTcPeopleDevelopment'));
    }

    public function showEvaluasiPDF($id)
    {
        $data = TcPeopleDevelopment::with([
            'user:id,npk,name',
            'participants:id,npk,name',
            'section:id,name',
        ])->findOrFail($id);
        $this->trainingEvaluation->assertCanViewResult(Auth::user(), $data);

        return response()->json($this->trainingEvaluation->payload($data));
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ], [
            'username.required' => 'Nama pengguna wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $credentials = [
            'username' => $request->username,
            'password' => $request->password, // Ubah dari 'pass' ke 'password'
        ];

        // Tambahkan kondisi untuk memeriksa is_active pengguna
        $user = User::where('username', $request->username)->first();

        if ($user && $user->is_active == 1) {
            // Jika is_active adalah 1, artinya pengguna tidak dapat login
            return redirect()->route('login')->with('error', 'Akun Anda tidak aktif. Silakan hubungi Section IT.');
        }

        if (Auth::attempt($credentials)) {
            // Autentikasi berhasil, tampilkan data login di console
            Log::info('Pengguna berhasil masuk.', ['username' => $request->username]);

            return redirect()->route('forumSS');
        }

        // Autentikasi gagal, tampilkan pesan error
        return redirect()->route('login')->with('error', 'Nama Pengguna & Kata Sandi tidak valid.');
    }

    public function logout()
    {
        Auth::logout();

        return redirect()->route('login');
    }

    public function ubahPassword(Request $request)
    {
        $user = Auth::user();

        // Validasi input
        $request->validate([
            'currentPassword' => 'required',
            'newPassword' => 'required|string|min:3|confirmed',
        ]);

        // Pastikan password saat ini benar
        if (!Hash::check($request->currentPassword, $user->password)) {
            return redirect()->back()->with('error', 'Password saat ini salah.');
        }

        // Hash password baru dengan bcrypt
        $user->password = bcrypt($request->newPassword);
        $user->pass = $request->newPassword; // Simpan password terbaru
        $user->save();

        // Redirect ke halaman profil dengan pesan sukses
        return redirect()->route('showDataDiri')->with('success', 'Password berhasil diubah.');
    }

    public function ubahDataDiri(Request $request)
    {
        $user = Auth::user();
        $user->telp = $request->telp;
        $user->email = $request->email;

        // Cek apakah ada file yang di-upload
        if ($request->hasFile('file')) {
            $file = $request->file('file');

            // Menghasilkan nama file dengan UUID
            $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $fileHash = hash_file('sha256', $file->getPathname()); // Menghitung hash file
            $destinationPath = public_path('assets/data_diri');

            // Hapus file lama jika ada
            if ($user->file_name) {
                $oldFile = public_path('assets/data_diri/' . $user->file_name);
                if (File::exists($oldFile)) {
                    File::delete($oldFile);
                }
            }

            // Pindahkan file ke folder tujuan
            $file->move($destinationPath, $fileName);

            // Simpan nama file yang dihasilkan UUID dan hash file
            $user->file = $fileHash; // Simpan hash untuk keperluan lain
            $user->file_name = $fileName; // Simpan nama file yang menggunakan UUID
        }


        $user->save();

        // Redirect ke halaman profil dengan pesan sukses
        return redirect()->route('showDataDiri')->with('success', 'Data diri berhasil diubah.');
    }
}
