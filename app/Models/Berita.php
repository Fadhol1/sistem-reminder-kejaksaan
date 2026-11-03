<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Berita extends Model
{
    use HasFactory;

    /**
     * Nama tabel database.
     *
     * @var string
     */
    protected $table = 'beritas';

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var list<string>
     */
    protected $fillable = [
        'judul',
        'slug',
        'ringkasan',
        'konten',
        'gambar',
        'tanggal',
        'penulis',
        'user_id',
        'is_published',
        'views_count',
    ];

    /**
     * Konversi tipe atribut.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tanggal'      => 'date:Y-m-d',
        'is_published' => 'boolean',
        'views_count'  => 'integer',
    ];

    /**
     * Accessor yang ditambahkan otomatis pada JSON response (untuk Android & API client).
     *
     * @var list<string>
     */
    protected $appends = [
        'gambar_url',
    ];

    /**
     * Boot model untuk otomatis membuat slug jika judul diisi dan slug kosong.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($berita) {
            if (empty($berita->slug) && !empty($berita->judul)) {
                $baseSlug = Str::slug($berita->judul);
                $slug = $baseSlug;
                $count = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . (++$count);
                }
                $berita->slug = $slug;
            }
        });
    }

    /**
     * Accessor untuk mendapatkan URL lengkap gambar.
     * Sangat berguna untuk client Android (Retrofit, Glide, Picasso, Coil).
     */
    public function getGambarUrlAttribute(): ?string
    {
        if (empty($this->gambar)) {
            return null;
        }

        // Jika path sudah berupa URL lengkap (misalnya seeder/eksternal)
        if (Str::startsWith($this->gambar, ['http://', 'https://'])) {
            return $this->gambar;
        }

        return asset('storage/' . ltrim($this->gambar, '/'));
    }

    /**
     * Relasi ke akun User (admin/petugas pembuat berita).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope hanya berita yang dipublikasikan.
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }
}

