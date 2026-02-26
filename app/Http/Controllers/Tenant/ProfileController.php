<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function index(): View
    {
        return view('tenant.settings.profile');
    }

    public function update(Request $request): JsonResponse
    {
        $user = auth()->user();

        $request->validate([
            'name'  => 'required|string|max:100',
            'email' => 'required|email|max:150|unique:users,email,' . $user->id,
        ]);

        $user->update([
            'name'  => $request->input('name'),
            'email' => $request->input('email'),
        ]);

        return response()->json(['success' => true, 'message' => 'Perfil atualizado com sucesso.']);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'password'         => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = auth()->user();

        if (!Hash::check($request->input('current_password'), $user->password)) {
            return response()->json([
                'success' => false,
                'errors'  => ['current_password' => ['A senha atual está incorreta.']],
            ], 422);
        }

        $user->update(['password' => $request->input('password')]);

        return response()->json(['success' => true, 'message' => 'Senha alterada com sucesso.']);
    }

    public function updateAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => 'required|image|max:2048',
        ]);

        $user = auth()->user();
        $file = $request->file('avatar');
        $path = 'avatars/' . $user->id . '.' . $file->extension();

        Storage::disk('public')->putFileAs('avatars', $file, $user->id . '.' . $file->extension());

        $user->update(['avatar' => Storage::disk('public')->url($path)]);

        return response()->json([
            'success'    => true,
            'avatar_url' => $user->avatar,
            'message'    => 'Foto atualizada com sucesso.',
        ]);
    }

    public function uploadWorkspaceLogo(Request $request): JsonResponse
    {
        $request->validate([
            'logo' => 'required|image|max:2048',
        ]);

        $user   = auth()->user();
        $tenant = $user->tenant;

        abort_unless($tenant && $user->isAdmin(), 403);

        $file = $request->file('logo');
        $path = 'workspace-logos/' . $tenant->id . '.' . $file->extension();

        Storage::disk('public')->putFileAs('workspace-logos', $file, $tenant->id . '.' . $file->extension());

        $tenant->update(['logo' => Storage::disk('public')->url($path)]);

        return response()->json([
            'success'   => true,
            'logo_url'  => $tenant->logo,
            'message'   => 'Logo do workspace atualizado com sucesso.',
        ]);
    }
}
