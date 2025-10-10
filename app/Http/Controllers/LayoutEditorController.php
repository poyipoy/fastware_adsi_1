<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class LayoutEditorController extends Controller
{
    /**
     * Display the current layout file in a simple editor.
     */
    public function edit()
    {
        $layoutPath = resource_path('views/layout.blade.php');

        if (! File::exists($layoutPath)) {
            abort(404, 'File layout.blade.php tidak ditemukan.');
        }

        $content = File::get($layoutPath);

        return view('layout_editor.edit', [
            'content' => $content,
        ]);
    }

    /**
     * Persist the updated layout file.
     */
    public function update(Request $request)
    {
        $request->validate([
            'layout_content' => 'required|string',
        ]);

        $layoutPath = resource_path('views/layout.blade.php');

        if (! File::exists($layoutPath)) {
            abort(404, 'File layout.blade.php tidak ditemukan.');
        }

        try {
            File::put($layoutPath, $request->input('layout_content'));
        } catch (\Throwable $throwable) {
            Log::error('Gagal memperbarui layout.blade.php', [
                'message' => $throwable->getMessage(),
            ]);

            return back()->withErrors('Gagal menyimpan perubahan. Silakan coba lagi.');
        }

        return back()->with('status', 'Layout berhasil disimpan.');
    }
}

