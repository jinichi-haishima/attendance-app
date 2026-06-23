<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class LoginTest extends TestCase
{

    use RefreshDatabase;
    
    private string $url = '/login';

    /** @test
     * メールアドレスが未入力の場合
     */

    public function test_email_is_required(): void
    {
        $user = User::create([
            'name'     => 'テストユーザー',
            'email'    => 'test_login@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post($this->url, [
            'email'    => '',
            'password' => 'password123',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['email']);

    }

    /** @test
     * パスワードが未入力の場合
     */
    public function test_password_is_required(): void
    {
        $user = User::create([
            'name'     => 'テストユーザー',
            'email'    => 'test_login@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post($this->url, [
            'email'    => $user->email,
            'password' => '',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['password']);
    }

    /** @test
     * 登録内容と一致しない場合
     */
    public function test_invalid_credentials(): void
    {
        $user = User::create([
            'name'     => 'テストユーザー',
            'email'    => 'test_login@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post($this->url, [
            'email'    => $user->email,
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません'
        ]);
    }
}