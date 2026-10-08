<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Perkara;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        abort_unless($request->user()->isPidum(), 403, 'Akses Ditolak: Hanya Admin PIDUM yang dapat mengelola pengguna.');

        $query = User::query()->orderBy('name', 'asc');

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('role') && $request->role != '') {
            $query->where('role', $request->role);
        }

        $users = $query->paginate(15);

        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        abort_unless($request->user()->isPidum(), 403, 'Akses Ditolak: Hanya Admin PIDUM yang dapat menambah pengguna.');
        
        return view('users.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        abort_unless($request->user()->isPidum(), 403, 'Akses Ditolak: Hanya Admin PIDUM yang dapat menyimpan pengguna.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'role' => ['required', 'string', 'in:jaksa,penyidik,pidum'],
            'nip' => ['nullable', 'string', 'max:50', 'unique:users'],
            'pangkat_golongan' => ['nullable', 'string', 'max:255'],
            'jabatan' => ['nullable', 'string', 'max:255'],
            'foto_profil' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $fotoPath = null;
        if ($request->hasFile('foto_profil')) {
            $fotoPath = $request->file('foto_profil')->store('profiles', 'public');
        }

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'nip' => $validated['nip'] ?? null,
            'pangkat_golongan' => $validated['pangkat_golongan'] ?? null,
            'jabatan' => $validated['jabatan'] ?? null,
            'foto_profil' => $fotoPath,
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, string $id)
    {
        abort_unless($request->user()->isPidum(), 403, 'Akses Ditolak: Hanya Admin PIDUM yang dapat mengedit pengguna.');

        $user = User::findOrFail($id);
        
        return view('users.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        abort_unless($request->user()->isPidum(), 403, 'Akses Ditolak: Hanya Admin PIDUM yang dapat memperbarui pengguna.');

        $user = User::findOrFail($id);

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'role' => ['required', 'string', 'in:jaksa,penyidik,pidum'],
            'nip' => ['nullable', 'string', 'max:50', 'unique:users,nip,'.$user->id],
            'pangkat_golongan' => ['nullable', 'string', 'max:255'],
            'jabatan' => ['nullable', 'string', 'max:255'],
            'foto_profil' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ];

        // Validasi password opsional (hanya diisi jika admin ingin mengubah password user)
        if ($request->filled('password')) {
            $rules['password'] = ['required', 'confirmed', Rules\Password::defaults()];
        }

        $validated = $request->validate($rules);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->nip = $validated['nip'] ?? null;
        $user->pangkat_golongan = $validated['pangkat_golongan'] ?? null;
        $user->jabatan = $validated['jabatan'] ?? null;
        
        if ($request->hasFile('foto_profil')) {
            if ($user->foto_profil && Storage::disk('public')->exists($user->foto_profil)) {
                Storage::disk('public')->delete($user->foto_profil);
            }
            $user->foto_profil = $request->file('foto_profil')->store('profiles', 'public');
        }
        
        // Prevent admin from changing their own role maliciously and locking themselves out
        if ($user->id !== $request->user()->id) {
            $user->role = $validated['role'];
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('users.index')->with('success', 'Profil pengguna berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        abort_unless($request->user()->isPidum(), 403, 'Akses Ditolak: Hanya Admin PIDUM yang dapat menghapus pengguna.');

        $user = User::findOrFail($id);

        // 1. Prevent suicide deletion
        if ($user->id === $request->user()->id) {
            return redirect()->back()->with('error', 'Keamanan: Anda tidak dapat menghapus akun Anda sendiri saat sedang login.');
        }

        // 2. Prevent deletion if tied to active PERKARA timeline or mapping
        $isActiveInPerkara = $user->perkaras()->exists() || Perkara::where('penyidik', $user->name)->exists();
        
        if ($isActiveInPerkara) {
            return redirect()->back()->with('error', 'Keamanan: Akun ini tidak dapat dihapus karena tercatat sedang/pernah memegang Perkara. Anda disarankan untuk menonaktifkan atau mengubah passwordnya jika ingin menutup akses.');
        }

        if ($user->foto_profil && Storage::disk('public')->exists($user->foto_profil)) {
            Storage::disk('public')->delete($user->foto_profil);
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil dihapus.');
    }
}
