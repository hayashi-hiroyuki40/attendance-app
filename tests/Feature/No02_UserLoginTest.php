<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class No02_UserLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_メールアドレスが未入力の場合_バリデーションメッセージが表示される()
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->post('/login', [
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    public function test_パスワードが未入力の場合_バリデーションメッセージが表示される()
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->post('/login', [
            'email' => $user->email,
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    public function test_登録内容と一致しない場合_バリデーションメッセージが表示される()
    {
        User::factory()->create(['role' => 'user', 'email' => 'this@example.com']);

        $response = $this->post('/login', [
            'email' => 'wrong@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }
}
