<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Mobile App Login Endpoint
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau Kata Sandi salah.',
            ], 401);
        }

        // Revoke all previous tokens for security parity on mobile login? Optional.
        // $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Login Berhasil',
            'data' => [
                'user' => $user,
                'token' => $user->createToken('android-app-token')->plainTextToken,
            ]
        ]);
    }

    /**
     * Update Profile Endpoint
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $rules = [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'nip' => ['nullable', 'string', 'max:50', Rule::unique('users')->ignore($user->id)],
            'pangkat_golongan' => ['nullable', 'string', 'max:255'],
            'jabatan' => ['nullable', 'string', 'max:255'],
            'foto_profil' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];

        $validated = $request->validate($rules);

        if ($request->has('name')) $user->name = $validated['name'];
        if ($request->has('email')) $user->email = $validated['email'];
        if ($request->has('nip')) $user->nip = $validated['nip'];
        if ($request->has('pangkat_golongan')) $user->pangkat_golongan = $validated['pangkat_golongan'];
        if ($request->has('jabatan')) $user->jabatan = $validated['jabatan'];

        if ($request->hasFile('foto_profil')) {
            if ($user->foto_profil && Storage::disk('public')->exists($user->foto_profil)) {
                Storage::disk('public')->delete($user->foto_profil);
            }
            $user->foto_profil = $request->file('foto_profil')->store('profiles', 'public');
            
            // Format to returning absolute URL for mobile convenience
            $user->foto_profil_url = Storage::url($user->foto_profil);
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui',
            'data' => [
                'user' => $user
            ]
        ]);
    }

    /**
     * Get Current Authenticated User Data
     */
    public function user(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => [
                'user' => $request->user()
            ]
        ]);
    }

    /**
     * Mobile App Logout Endpoint
     */
    public function logout(Request $request)
    {
        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil logout'
        ]);
    }
}
