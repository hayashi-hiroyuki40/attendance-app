<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class No16_EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_会員登録後_認証メールが送信される()
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'test@example.com')->first();

        Notification::assertSentTo(
            [$user],
            VerifyEmail::class
        );
    }

    public function test_メール認証誘導画面でボタンを押下するとメール認証サイトに遷移する()
    {
        $this->withoutExceptionHandling();
        $user = User::factory()->create([
            'email_verified_at' => null,
            'admin_status' => false,
        ]);

        $response = $this->actingAs($user)->get('/email/verify');
        $response->assertStatus(200);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        $response->assertRedirect('/attendance');
    }

    public function test_メール認証サイトのメール認証を完了すると勤怠登録画面に遷移する()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'admin_status' => false,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );

        $this->actingAs($user)->get($verificationUrl);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
    }
}
