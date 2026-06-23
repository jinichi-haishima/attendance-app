<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class LoginTest extends TestCase
{
    use RefreshDatabase;
    
    /**
     * メールアドレスが未入力の場合、バリデーションメッセージが表示される
     */

    public function test_admin_email_is_required(): void
    {
        $user = User::create([
            'name'     => 'テストユーザー',
            'email'    => 'test_admin@example.com',
            'password' => bcrypt('password123'),
            'is_admin' => true,
        ]);

        $response = $this->post('/admin/login', [
            'email'    => '',
            'password' => 'password123',
        ]);
        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください'
        ]);
    }
    /**
     * パスワードが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_admin_password_is_required(): void
    {
        $user = User::create([
            'name'     => 'テストユーザー',
            'email'    => 'test_admin@example.com',
            'password' => bcrypt('password123'),
            'is_admin' => true,
        ]);

        $response = $this->post('admin/login', [
            'email'    => $user->email,
            'password' => '',
        ]);
        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください'
        ]);
    }
    /**
     * 登録内容と一致しない場合、バリデーションメッセージが表示される
     */
    public function test_admin_invalid_credentials(): void
    {
        $user = User::create([
            'name'     => 'テストユーザー',
            'email'    => 'test_admin@example.com',
            'password' => bcrypt('password123'),
            'is_admin' => true,
        ]);

        $response = $this->post('admin/login', [
            'email'    => $user->email,
            'password' => 'wrongpassword',
        ]);
        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません'
        ]);
    }
}