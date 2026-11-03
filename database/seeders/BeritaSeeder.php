<?php

namespace Database\Seeders;

use App\Models\Berita;
use App\Models\User;
use Illuminate\Database\Seeder;

class BeritaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::first();

        $sampleNews = [
            [
                'judul'        => 'Kejaksaan Negeri Gelar Penyuluhan Hukum Terkait Restorative Justice',
                'slug'         => 'kejaksaan-negeri-gelar-penyuluhan-hukum-terkait-restorative-justice',
                'ringkasan'    => 'Kejaksaan Negeri mengedukasi masyarakat mengenai mekanisme penyelesaian tindak pidana melalui pendekatan keadilan restoratif secara transparan dan berkeadilan.',
                'konten'       => "Kejaksaan Negeri menggelar kegiatan penyuluhan hukum bertajuk 'Penyelesaian Perkara Berdasarkan Keadilan Restoratif (Restorative Justice)' yang dihadiri oleh jajaran Forkopimda, tokoh masyarakat, dan aparat penegak hukum setempat.\n\nDalam sambutannya, Kepala Kejaksaan Negeri menyampaikan bahwa keadilan restoratif merupakan langkah strategis untuk mewujudkan kepastian hukum yang berhati nurani, mengedepankan perdamaian, serta pemulihan keadaan semula bagi korban dan pelaku tindak pidana ringan sesuai dengan Peraturan Kejaksaan RI Nomor 15 Tahun 2020.",
                'gambar'       => null,
                'tanggal'      => '2026-11-03',
                'penulis'      => 'Seksi Intelijen & Humas',
                'user_id'      => $admin?->id,
                'is_published' => true,
                'views_count'  => 45,
            ],
            [
                'judul'        => 'Optimalisasi Sistem SIPETA: Notifikasi Batas Waktu Prapenuntutan Berbasis Realtime',
                'slug'         => 'optimalisasi-sistem-sipeta-notifikasi-batas-waktu-prapenuntutan-berbasis-realtime',
                'ringkasan'    => 'Peningkatan pengawasan administrasi perkara SPDP dan berkas tahap I melalui implementasi sistem pengingat otomatis bagi Jaksa Peneliti.',
                'konten'       => "Seksi Tindak Pidana Umum terus memperkuat pemantauan penanganan perkara dengan mengoptimalkan aplikasi SIPETA (Sistem Pengingat Tahapan Perkara). Sistem ini memastikan setiap tahapan berkas perkara, mulai dari penerimaan SPDP, P-16, hingga koordinasi dengan pihak penyidik kepolisian, dapat dipantau tepat waktu guna menghindari kadaluwarsa masa penanganan berkas perkara.",
                'gambar'       => null,
                'tanggal'      => '2026-11-02',
                'penulis'      => 'Seksi Tindak Pidana Umum',
                'user_id'      => $admin?->id,
                'is_published' => true,
                'views_count'  => 82,
            ],
            [
                'judul'        => 'Pemusnahan Barang Bukti Perkara Pidana Umum yang Telah Berkekuatan Hukum Tetap (Inkracht)',
                'slug'         => 'pemusnahan-barang-bukti-perkara-pidana-umum-yang-telah-berkekuatan-hukum-tetap-inkracht',
                'ringkasan'    => 'Kegiatan pemusnahan barang bukti narkotika, senjata tajam, dan barang bukti tindak pidana lainnya dilaksanakan secara terbuka dan akuntabel.',
                'konten'       => "Kejaksaan Negeri melaksanakan eksekusi pemusnahan barang bukti atas puluhan perkara tindak pidana umum yang telah memperoleh putusan berkekuatan hukum tetap (inkracht van gewijsde). Kegiatan ini merupakan komitmen transparansi Korps Adhyaksa dalam menjaga profesionalisme dan integritas pengelolaan barang bukti hasil kejahatan.",
                'gambar'       => null,
                'tanggal'      => '2026-10-30',
                'penulis'      => 'Seksi Pengelolaan Barang Bukti & Rampasan',
                'user_id'      => $admin?->id,
                'is_published' => true,
                'views_count'  => 118,
            ],
        ];

        foreach ($sampleNews as $data) {
            Berita::firstOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }
    }
}

