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

        // Support parameter aliases: no_hp, phone, phone_number, telepon -> nomor_hp
        if (!$request->has('nomor_hp')) {
            $aliasHp = $request->input('no_hp') 
                ?? $request->input('phone') 
                ?? $request->input('phone_number') 
                ?? $request->input('nomor_telepon')
                ?? $request->input('telepon');
            if ($aliasHp !== null) {
                $request->merge(['nomor_hp' => $aliasHp]);
            }
        }

        $rules = [
            'name'            => ['sometimes', 'required', 'string', 'max:255'],
            'email'           => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'nip'             => ['nullable', 'string', 'max:50', Rule::unique('users')->ignore($user->id)],
            'nrp'             => ['nullable', 'string', 'max:50', Rule::unique('users')->ignore($user->id)],
            'nomor_hp'        => ['nullable', 'string', 'max:20'],
            'pangkat_golongan'=> ['nullable', 'string', 'max:255'],
            'jabatan'         => ['nullable', 'string', 'max:255'],
            'foto_profil'     => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'password'        => ['nullable', 'string', 'min:8', 'confirmed'],
        ];

        $validated = $request->validate($rules);

        if ($request->has('name'))             $user->name = $validated['name'];
        if ($request->has('email'))            $user->email = $validated['email'];
        if ($request->has('nip'))              $user->nip = $validated['nip'];
        if ($request->has('nrp'))              $user->nrp = $validated['nrp'];
        if ($request->has('nomor_hp'))         $user->nomor_hp = $validated['nomor_hp'];
        if ($request->has('pangkat_golongan')) $user->pangkat_golongan = $validated['pangkat_golongan'];
        if ($request->has('jabatan'))          $user->jabatan = $validated['jabatan'];

        if ($request->hasFile('foto_profil')) {
            if ($user->foto_profil && Storage::disk('public')->exists($user->foto_profil)) {
                Storage::disk('public')->delete($user->foto_profil);
            }
            $user->foto_profil = $request->file('foto_profil')->store('profiles', 'public');
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();
        $user->refresh();

        // Append absolute foto_profil_url for mobile convenience
        $profileData = $user->toArray();
        $profileData['foto_profil_url'] = $user->foto_profil
            ? Storage::url($user->foto_profil)
            : null;

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui',
            'data'    => [
                'user'     => $profileData,
                'nomor_hp' => $user->nomor_hp,
            ]
        ]);
    }

    /**
     * Dedicated Update Nomor HP Endpoint
     * Supports: PUT/PATCH/POST /api/user/nomor-hp, /api/profile/nomor-hp, /api/nomor-hp
     */
    public function updateNomorHp(Request $request)
    {
        $user = $request->user();

        // Support parameter aliases: nomor_hp, no_hp, phone, phone_number, telepon
        $nomorHp = $request->input('nomor_hp') 
            ?? $request->input('no_hp') 
            ?? $request->input('phone') 
            ?? $request->input('phone_number')
            ?? $request->input('nomor_telepon')
            ?? $request->input('telepon');

        $request->merge(['nomor_hp' => $nomorHp]);

        $validated = $request->validate([
            'nomor_hp' => ['required', 'string', 'max:20'],
        ], [
            'nomor_hp.required' => 'Nomor HP wajib diisi.',
            'nomor_hp.max'      => 'Nomor HP maksimal 20 karakter.',
        ]);

        $user->nomor_hp = $validated['nomor_hp'];
        $user->save();
        $user->refresh();

        $profileData = $user->toArray();
        $profileData['foto_profil_url'] = $user->foto_profil
            ? Storage::url($user->foto_profil)
            : null;

        return response()->json([
            'success' => true,
            'message' => 'Nomor HP berhasil diperbarui',
            'data'    => [
                'nomor_hp' => $user->nomor_hp,
                'user'     => $profileData,
            ]
        ]);
    }

    /**
     * Get Current Authenticated User Data (GET /api/profile)
     */
    public function getProfile(Request $request)
    {
        $user = $request->user();

        $profileData = $user->toArray();
        $profileData['foto_profil_url'] = $user->foto_profil
            ? Storage::url($user->foto_profil)
            : null;

        return response()->json([
            'success' => true,
            'data'    => ['user' => $profileData]
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
     * Dedicated Change Password Endpoint (secure - requires current password)
     */
    public function changePassword(Request $request)
    {
        $user = $request->user();

        // Support parameter aliases: new_password -> password
        if (!$request->has('password') && $request->has('new_password')) {
            $request->merge([
                'password' => $request->input('new_password'),
                'password_confirmation' => $request->input('new_password_confirmation') ?? $request->input('password_confirmation'),
            ]);
        }

        $request->validate([
            'current_password'  => ['required', 'string'],
            'password'          => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Verifikasi kata sandi lama sebelum izinkan perubahan
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Kata sandi saat ini tidak sesuai.',
                'errors'  => ['current_password' => ['Kata sandi saat ini tidak sesuai.']]
            ], 422);
        }

        // Cegah penggunaan password yang sama persis dengan yang lama
        if (Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Kata sandi baru tidak boleh sama dengan kata sandi lama.',
                'errors'  => ['password' => ['Kata sandi baru tidak boleh sama dengan kata sandi lama.']]
            ], 422);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        // Revoke semua token lama kecuali yang sedang dipakai (opsional tapi lebih aman)
        $currentTokenId = $user->currentAccessToken()->id;
        $user->tokens()->where('id', '!=', $currentTokenId)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kata sandi berhasil diperbarui. Sesi lain telah diakhiri.'
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
