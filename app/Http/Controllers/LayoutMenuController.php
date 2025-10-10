<?php

namespace App\Http\Controllers;

use App\Services\MenuAccessStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class LayoutMenuController extends Controller
{
    public function __construct(private readonly MenuAccessStorage $menuAccess)
    {
    }

    public function index(): JsonResponse
    {
        $this->authorizeAccess();

        return response()->json([
            'data' => $this->menuAccess->all(),
        ]);
    }

    public function edit()
    {
        $this->authorizeAccess();

        return view('admin.layout-menu.edit', [
            'groups' => $this->menuAccess->all(),
        ]);
    }

    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess();

        $validator = Validator::make($request->all(), [
            'groups' => ['array'],
            'new_group.key' => ['nullable', 'string'],
            'new_group.users' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()->toArray(),
                ], SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY);
            }

            return back()->withErrors($validator)->withInput();
        }

        $groups = $request->input('groups', []);

        foreach ($groups as $key => $payload) {
            $key = Str::of($key)->trim()->lower()->__toString();
            if ($key === '') {
                continue;
            }

            if (!empty($payload['delete'])) {
                $this->menuAccess->delete($key);
                continue;
            }

            $label = isset($payload['label']) ? Str::of($payload['label'])->trim()->__toString() : null;
            $users = $this->menuAccess->parseInput($payload['users'] ?? '');
            $this->menuAccess->upsert($key, $users, $label === '' ? null : $label);
        }

        $newGroup = $request->input('new_group', []);
        $newKey = Str::of($newGroup['key'] ?? '')->trim()->lower()->__toString();

        if ($newKey !== '') {
            $label = Str::of($newGroup['label'] ?? '')->trim()->__toString();
            $users = $this->menuAccess->parseInput($newGroup['users'] ?? '');
            $this->menuAccess->upsert($newKey, $users, $label === '' ? null : $label);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Data akses berhasil diperbarui.',
                'data' => $this->menuAccess->all(),
            ]);
        }

        return back()->with('status', 'Daftar allowed users berhasil disimpan.');
    }

    private function authorizeAccess(): void
    {
        $user = auth()->user();

        if (!$user || !in_array($user->role_id, [1], true)) {
            abort(403);
        }
    }
}
