<?php

namespace Tests\Feature;

use App\Models\Berita;
use App\Models\PushNotificationLog;
use App\Models\User;
use App\Models\UserDeviceToken;
use App\Services\Fcm\FcmSenderService;
use App\Services\Notification\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BeritaPushNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $pidum;
    protected User $jaksa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pidum = User::factory()->create([
            'name' => 'Admin PIDUM',
            'email' => 'pidum.berita@kejaksaan.go.id',
            'role' => 'pidum',
        ]);

        $this->jaksa = User::factory()->create([
            'name' => 'Jaksa Penuntut',
            'email' => 'jaksa.berita@kejaksaan.go.id',
            'role' => 'jaksa',
        ]);
    }

    public function test_publishing_berita_sends_push_notification_to_jaksa_and_active_devices(): void
    {
        $jaksaToken = 'fcm_jaksa_berita_token_123456';
        UserDeviceToken::create([
            'user_id' => $this->jaksa->id,
            'device_id' => 'hp_jaksa',
            'device_type' => 'android',
            'fcm_token' => $jaksaToken,
            'fcm_token_hash' => UserDeviceToken::hashToken($jaksaToken),
            'is_active' => true,
        ]);

        $mockFcm = $this->createMock(FcmSenderService::class);
        $capturedPayload = null;

        $mockFcm->expects($this->once())
            ->method('sendToToken')
            ->willReturnCallback(function ($deviceToken, $title, $body, $data) use (&$capturedPayload) {
                $capturedPayload = [
                    'deviceToken' => $deviceToken,
                    'title' => $title,
                    'body' => $body,
                    'data' => $data,
                ];

                return [
                    'success' => true,
                    'message_id' => 'projects/reminderskejaksaan/messages/berita_msg_001',
                    'error' => null,
                    'is_invalid_token' => false,
                ];
            });

        $this->app->instance(FcmSenderService::class, $mockFcm);

        $response = $this->actingAs($this->pidum)->post(route('berita.store'), [
            'judul' => 'Sosialisasi KUHP Baru Kejaksaan Negeri OKU',
            'tanggal' => now()->toDateString(),
            'ringkasan' => 'Kegiatan sosialisasi dilaksanakan bersama seluruh jaksa.',
            'konten' => '<p>Isi lengkap berita sosialisasi.</p>',
            'is_published' => '1',
        ]);

        $response->assertRedirect(route('berita.index'));

        $berita = Berita::firstOrFail();

        $this->assertNotNull($capturedPayload);
        $this->assertEquals($jaksaToken, $capturedPayload['deviceToken']);
        $this->assertStringContainsString('Sosialisasi KUHP Baru', $capturedPayload['title']);
        $this->assertEquals((string) $berita->id, $capturedPayload['data']['berita_id']);
        $this->assertEquals(NotificationService::EVENT_BERITA_PUBLISHED, $capturedPayload['data']['event_type']);
        $this->assertEquals('OPEN_BERITA_DETAIL', $capturedPayload['data']['click_action']);

        $this->assertDatabaseHas('push_notification_logs', [
            'user_id' => $this->jaksa->id,
            'perkara_id' => null,
            'event_type' => NotificationService::EVENT_BERITA_PUBLISHED,
            'idempotency_key' => "berita_{$berita->id}_pub_u{$this->jaksa->id}",
            'status' => PushNotificationLog::STATUS_SENT,
        ]);
    }

    public function test_saving_draft_berita_does_not_send_notification_until_published(): void
    {
        $jaksaToken = 'fcm_jaksa_draft_token_123456';
        UserDeviceToken::create([
            'user_id' => $this->jaksa->id,
            'device_id' => 'hp_jaksa_draft',
            'device_type' => 'android',
            'fcm_token' => $jaksaToken,
            'fcm_token_hash' => UserDeviceToken::hashToken($jaksaToken),
            'is_active' => true,
        ]);

        $mockFcm = $this->createMock(FcmSenderService::class);
        $mockFcm->expects($this->once())
            ->method('sendToToken')
            ->willReturn([
                'success' => true,
                'message_id' => 'projects/reminderskejaksaan/messages/berita_msg_002',
                'error' => null,
                'is_invalid_token' => false,
            ]);

        $this->app->instance(FcmSenderService::class, $mockFcm);

        // 1. Simpan sebagai draf (is_published = 0) -> tidak boleh mengirim notifikasi
        $this->actingAs($this->pidum)->post(route('berita.store'), [
            'judul' => 'Berita Draf Awal',
            'tanggal' => now()->toDateString(),
            'konten' => 'Konten draf.',
            'is_published' => '0',
        ])->assertRedirect(route('berita.index'));

        $berita = Berita::firstOrFail();
        $this->assertFalse($berita->is_published);
        $this->assertEquals(0, PushNotificationLog::count());

        // 2. Ubah status draf -> terbit (is_published = 1) -> harus mengirim tepat 1 kali
        $this->actingAs($this->pidum)->put(route('berita.update', $berita->id), [
            'judul' => 'Berita Resmi Terbit',
            'tanggal' => now()->toDateString(),
            'konten' => 'Konten final.',
            'is_published' => '1',
        ])->assertRedirect(route('berita.index'));

        $this->assertEquals(1, PushNotificationLog::where('event_type', NotificationService::EVENT_BERITA_PUBLISHED)->count());

        // 3. Edit ulang berita yang sudah terbit -> tidak boleh mengirim notifikasi ulang
        $this->actingAs($this->pidum)->put(route('berita.update', $berita->id), [
            'judul' => 'Berita Resmi Terbit (Revisi Typo)',
            'tanggal' => now()->toDateString(),
            'konten' => 'Konten final revisi.',
            'is_published' => '1',
        ])->assertRedirect(route('berita.index'));

        $this->assertEquals(1, PushNotificationLog::where('event_type', NotificationService::EVENT_BERITA_PUBLISHED)->count());
    }

    public function test_fcm_sender_uses_custom_click_action_from_data_payload(): void
    {
        Http::fake([
            'https://fcm.googleapis.com/*' => Http::response([
                'name' => 'projects/reminderskejaksaan/messages/mock_123',
            ], 200),
        ]);

        $sender = new class extends FcmSenderService {
            public function getAccessToken(): ?string
            {
                return 'fake-oauth-token';
            }
        };

        $result = $sender->sendToToken(
            deviceToken: 'valid_device_token_123456',
            title: 'Berita Baru',
            body: 'Ringkasan berita',
            data: [
                'berita_id' => '99',
                'click_action' => 'OPEN_BERITA_DETAIL',
            ]
        );

        $this->assertTrue($result['success']);

        Http::assertSent(function ($request) {
            $body = $request->data();
            return ($body['message']['android']['notification']['click_action'] ?? null) === 'OPEN_BERITA_DETAIL'
                && ($body['message']['data']['berita_id'] ?? null) === '99';
        });
    }
}

