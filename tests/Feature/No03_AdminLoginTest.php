<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class No03_AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_メールアドレスが未入力の場合_バリデーションメッセージが表示される()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->post('/admin/login', [
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    public function test_パスワードが未入力の場合_バリデーションメッセージが表示される()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->post('/admin/login', [
            'email' => $admin->email,
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    public function test_登録内容と一致しない場合_バリデーションメッセージが表示される()
    {
        User::factory()->create(['role' => 'admin', 'email' => 'admin@example.com']);

        $response = $this->post('/admin/login', [
            'email' => 'wrong@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }
}
