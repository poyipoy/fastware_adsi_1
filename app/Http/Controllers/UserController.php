<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = User::where('is_active', 0)->latest()->get();

        return view('admin.index', compact('users'))->with('i', (request()->input('page', 1) - 1) * 5);
    }

    public function create()
    {
        $roles = Role::all();

        return view('admin.create', compact('roles'));
    }

    public function edit(User $user)
    {
        $roles = Role::all();

        return view('admin.edit', compact('user', 'roles'));
    }

    public function show(User $user)
    {
        $roles = Role::all();

        return view('admin.show', compact('user', 'roles'));
    }


    public function update(Request $request, User $user)
    {
        // Handle file upload
        if ($request->hasFile('file')) {
            // Delete the old file if it exists
            if ($user->file) {
                $oldFilePath = public_path('assets/data_diri/' . $user->file);
                if (file_exists($oldFilePath)) {
                    unlink($oldFilePath);
                }
            }

            // Generate a new UUID for the file and store it in the public path
            $file = $request->file('file');
            $file_uuid = Str::uuid();
            $file_name = $file->getClientOriginalName();
            $file_extension = $file->getClientOriginalExtension();
            $file_path = 'assets/data_diri/' . $file_uuid . '.' . $file_extension;

            // Move the file to the specified directory
            $file->move(public_path('assets/data_diri'), $file_uuid . '.' . $file_extension);

            // Update user with new file information
            $user->file = $file_uuid . '.' . $file_extension; // Storing just the filename, not full path
            $user->file_name = $file_name;
        }

        // Update other fields
        $user->update([
            'role_id' => $request->role_id ?? $user->role_id,
            'name' => $request->nama ?? $user->name,
            'npk' => $request->npk ?? $user->npk,
            'username' => $request->username ?? $user->username,
            'password' => $request->pass ? bcrypt($request->pass) : $user->password,
            'pass' => $request->pass ?? $user->pass,
            'email' => $request->email ?? $user->email,
            'telp' => $request->telp ?? $user->telp,
        ]);

        $user->save();

        return redirect()->route('users.index')->with('success', 'User updated successfully');
    }


    public function store(Request $request)
    {
        $request->merge(['is_active' => $request->is_active ?? 0]);

        User::create([
            'role_id' => $request->role_id,
            'name' => $request->name,
            'npk' => $request->npk,
            'username' => $request->username,
            'password' => bcrypt($request->pass), // Mengenkripsi password menggunakan bcrypt()
            'pass' => $request->pass,
            'email' => $request->email,
            'telp' => $request->telp,
            'is_active' => $request->is_active
        ]);

        return redirect()->route('users.index')->with('success', 'User berhasil dibuat');
    }

    public function destroy(User $user)
    {
        $user->is_active = 1; // Set is_active menjadi 1 sebelum menghapus pengguna
        $user->save(); // Simpan perubahan

        return redirect()->route('users.index')->with('success', 'User berhasil dihapus');
    }
}
