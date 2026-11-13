<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDeviceToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

        // Proteksi Brute Force API: Maksimal 5x percobaan dalam 1 menit
        $throttleKey = Str::transliterate(Str::lower($request->input('email')) . '|' . $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return response()->json([
                'success' => false,
                'message' => "Terlalu banyak percobaan masuk yang gagal. Akses dibatasi sementara selama {$seconds} detik demi keamanan akun kedinasan.",
            ], 429);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            RateLimiter::hit($throttleKey, 60);

            return response()->json([
                'success' => false,
                'message' => 'Email atau Kata Sandi salah.',
            ], 401);
        }

        // Hapus penalti jika login berhasil
        RateLimiter::clear($throttleKey);

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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'nip' => ['nullable', 'string', 'max:50', Rule::unique('users')->ignore($user->id)],
            'nrp' => ['nullable', 'string', 'max:50', Rule::unique('users')->ignore($user->id)],
            'nomor_hp' => ['nullable', 'string', 'max:20'],
            'pangkat_golongan' => ['nullable', 'string', 'max:255'],
            'jabatan' => ['nullable', 'string', 'max:255'],
            'foto_profil' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ];

        $passwordChanged = $request->filled('password');
        $throttleKey = 'pwd_check_' . $user->id . '|' . $request->ip();

        if ($passwordChanged) {
            if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
                $seconds = RateLimiter::availableIn($throttleKey);
                return response()->json([
                    'success' => false,
                    'message' => "Terlalu banyak percobaan kata sandi yang salah. Silakan coba lagi dalam {$seconds} detik.",
                ], 429);
            }

            $rules['current_password'] = ['required', 'string'];
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed', 'different:current_password'];
        }

        $validated = $request->validate($rules, [
            'current_password.required' => 'Kata sandi saat ini wajib diisi untuk memperbarui kata sandi.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak sesuai.',
            'password.different' => 'Kata sandi baru tidak boleh sama dengan kata sandi saat ini.',
            'password.min' => 'Kata sandi baru minimal 8 karakter.',
        ]);

        if ($passwordChanged) {
            if (!Hash::check($validated['current_password'], $user->password)) {
                RateLimiter::hit($throttleKey, 60);
                return response()->json([
                    'success' => false,
                    'message' => 'Kata sandi saat ini tidak sesuai.',
                    'errors' => ['current_password' => ['Kata sandi saat ini tidak sesuai.']],
                ], 422);
            }

            RateLimiter::clear($throttleKey);
        }

        if ($request->has('name'))
            $user->name = $validated['name'];
        if ($request->has('email'))
            $user->email = $validated['email'];
        if ($request->has('nip'))
            $user->nip = $validated['nip'];
        if ($request->has('nrp'))
            $user->nrp = $validated['nrp'];
        if ($request->has('nomor_hp'))
            $user->nomor_hp = $validated['nomor_hp'];
        if ($request->has('pangkat_golongan'))
            $user->pangkat_golongan = $validated['pangkat_golongan'];
        if ($request->has('jabatan'))
            $user->jabatan = $validated['jabatan'];

        $photoFile = $request->file('foto_profil')
            ?? $request->file('foto')
            ?? $request->file('photo')
            ?? $request->file('avatar')
            ?? $request->file('image')
            ?? $request->file('file');

        if ($photoFile) {
            if ($user->foto_profil && Storage::disk('public')->exists($user->foto_profil)) {
                Storage::disk('public')->delete($user->foto_profil);
            }
            $user->foto_profil = $photoFile->store('profiles', 'public');
        }

        if ($passwordChanged) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        if ($passwordChanged) {
            // Cabut seluruh token API Sanctum selain token yang sedang digunakan saat ini
            $currentTokenId = $user->currentAccessToken()?->id;
            if ($currentTokenId) {
                $user->tokens()->where('id', '!=', $currentTokenId)->delete();
            } else {
                $user->tokens()->delete();
            }

            // Nonaktifkan token perangkat FCM lama
            UserDeviceToken::where('user_id', $user->id)->update(['is_active' => false]);

            // Hapus sesi web aktif di database jika session driver database digunakan
            if (config('session.driver') === 'database') {
                DB::table(config('session.table', 'sessions'))
                    ->where('user_id', $user->id)
                    ->delete();
            }
        }

        $user->refresh();

        // Append absolute foto_profil_url for mobile convenience
        $profileData = $user->toArray();
        $profileData['foto_profil_url'] = $user->foto_profil_url;
        $profileData['foto_profile_url'] = $user->foto_profile_url;

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui',
            'data' => [
                'user' => $profileData,
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
            'nomor_hp.max' => 'Nomor HP maksimal 20 karakter.',
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
            'data' => [
                'nomor_hp' => $user->nomor_hp,
                'user' => $profileData,
            ]
        ]);
    }

    /**
     * Dedicated Upload / Update Foto Profil Endpoint
     * Mendukung file multipart (foto_profil, foto, photo, avatar, image, file)
     * dan format string Base64 JSON.
     */

    public function uploadFotoProfil(Request $request)
    {
        $user = $request->user();

        // 1. Deteksi file dari berbagai alias nama parameter yang umum dipakai di Android
        $file = $request->file('foto_profil')
            ?? $request->file('foto')
            ?? $request->file('photo')
            ?? $request->file('avatar')
            ?? $request->file('image')
            ?? $request->file('file');

        // 2. Deteksi input jika Android mengirim string Base64 dalam JSON body
        $base64Input = $request->input('foto_profil')
            ?? $request->input('foto')
            ?? $request->input('photo')
            ?? $request->input('avatar')
            ?? $request->input('image')
            ?? $request->input('base64');

        $newPath = null;

        if ($file) {
            // Validasi file upload (maks 5MB, format gambar standar termasuk jpg & png)
            $request->validate([
                'foto_profil' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif', 'max:5120'],
                'foto'        => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif', 'max:5120'],
                'photo'       => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif', 'max:5120'],
                'avatar'      => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif', 'max:5120'],
                'image'       => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif', 'max:5120'],
                'file'        => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif', 'max:5120'],
            ], [
                'max'   => 'Ukuran foto profil maksimal 5 MB.',
                'image' => 'Berkas yang diunggah harus berupa file gambar.',
                'mimes' => 'Format gambar harus berupa jpeg, png, jpg, webp, atau gif.',
            ]);

            // Hapus foto profil lama jika ada
            if ($user->foto_profil && Storage::disk('public')->exists($user->foto_profil)) {
                Storage::disk('public')->delete($user->foto_profil);
            }

            $newPath = $file->store('profiles', 'public');
        } elseif (!empty($base64Input) && is_string($base64Input)) {
            if (preg_match('/^data:image\/(\w+);base64,/', $base64Input, $type)) {
                $base64Data = substr($base64Input, strpos($base64Input, ',') + 1);
                $ext = strtolower($type[1]);
            } else {
                $base64Data = $base64Input;
                $ext = 'jpg';
            }

            $decoded = base64_decode($base64Data);
            if ($decoded !== false) {
                if ($user->foto_profil && Storage::disk('public')->exists($user->foto_profil)) {
                    Storage::disk('public')->delete($user->foto_profil);
                }

                $filename = 'profiles/' . Str::random(40) . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
                Storage::disk('public')->put($filename, $decoded);
                $newPath = $filename;
            }
        }

        if (!$newPath) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada berkas foto atau data gambar yang valid ditemukan.',
            ], 422);
        }

        $user->foto_profil = $newPath;
        $user->save();
        $user->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Foto profil berhasil diperbarui.',
            'data' => [
                'user'             => $user,
                'foto_profil'      => $user->foto_profil,
                'foto_profil_url'  => $user->foto_profil_url,
                'foto_profile_url' => $user->foto_profile_url,
            ],
        ], 200);
    }

    /**
     * Get Current Authenticated User Data (GET /api/profile)
     */
    public function getProfile(Request $request)
    {
        $user = $request->user();

        $profileData = $user->toArray();
        $profileData['foto_profil_url'] = $user->foto_profil_url;
        $profileData['foto_profile_url'] = $user->foto_profile_url;

        return response()->json([
            'success' => true,
            'data' => ['user' => $profileData]
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
        $throttleKey = 'pwd_check_' . $user->id . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return response()->json([
                'success' => false,
                'message' => "Terlalu banyak percobaan kata sandi yang salah. Silakan coba lagi dalam {$seconds} detik.",
            ], 429);
        }

        // Support parameter aliases: new_password -> password
        if (!$request->has('password') && $request->has('new_password')) {
            $request->merge([
                'password' => $request->input('new_password'),
                'password_confirmation' => $request->input('new_password_confirmation') ?? $request->input('password_confirmation'),
            ]);
        }

        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Verifikasi kata sandi lama sebelum izinkan perubahan
        if (!Hash::check($request->current_password, $user->password)) {
            RateLimiter::hit($throttleKey, 60);
            return response()->json([
                'success' => false,
                'message' => 'Kata sandi saat ini tidak sesuai.',
                'errors' => ['current_password' => ['Kata sandi saat ini tidak sesuai.']]
            ], 422);
        }

        RateLimiter::clear($throttleKey);

        // Cegah penggunaan password yang sama persis dengan yang lama
        if (Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Kata sandi baru tidak boleh sama dengan kata sandi lama.',
                'errors' => ['password' => ['Kata sandi baru tidak boleh sama dengan kata sandi lama.']]
            ], 422);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        // Revoke semua token lama kecuali yang sedang dipakai
        $currentTokenId = $user->currentAccessToken()?->id;
        if ($currentTokenId) {
            $user->tokens()->where('id', '!=', $currentTokenId)->delete();
        } else {
            $user->tokens()->delete();
        }

        // Nonaktifkan token perangkat FCM lama
        UserDeviceToken::where('user_id', $user->id)->update(['is_active' => false]);

        // Hapus sesi web aktif di database jika session driver database digunakan
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->delete();
        }

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
