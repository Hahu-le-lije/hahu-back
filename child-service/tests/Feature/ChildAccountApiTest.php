<?php

namespace Tests\Feature;

use App\Models\Child;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ChildAccountApiTest extends TestCase
{
    use RefreshDatabase;

    private string $clerkPrivateKey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clerkPrivateKey = <<<'PEM'
-----BEGIN PRIVATE KEY-----
MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQD4jEQ4P9vLQSBC
l0fR2SXxNtAe5VMq53FBtShA9z/Ie0bJRrSVkV2nfHTlE5022eN7/CgNYFsU9RFT
PCGBfzmxQodZUqMDYNiWKZf51OZytuje8zkwkUtIcD054FyLLKOBCqb+PjeLGScE
2qTaqOAxKm0bRHsvotpSa1AI6J4/kfwxOOhuWYzhgmRDNqkrMk76/quuUBRxd+cX
Qrd8P7XpT3kocPQzcyxtDpg8TOcdhMkTTSdurywUqLYFNDI3IORI3Zf7b3qsHlJo
B1mI583k+EVUmrx36iqMxLj3vnPOxU+d0UTSWJ3MFBiMeBYXVk/E3Er5R594hToK
M0M3y/VNAgMBAAECggEAaRtUHB9xMOaVIGP+NLFqOLQP1gjvn0SpofpckACfIgZz
3FlAs8F6BAJ/IoKlaNVCvKNZQrLdQaMTVQooNy2YtavnNfBazGpvnSzwvm94lslf
+CyOIkpHNlZ0pd2J9TcG2+Kn1Pt1nKah2A0oSunAiToiUrYmi/IH9nO4MFRC26lo
zxUXMpvr4ELUpCqP1fi1jfywdG37Q1U8SRS09+wwtRdvL8u4qNRYRef0D0H2La9N
w/OodqVbZwk47jRYhWJbRXGYdr8IGdKCdCGBuJhLbQRQua7yxVAD14t3vX88+U3u
5OnMQYYou8+/nTOmDGKsmYBT929Y7wlInrQWsJpSZwKBgQD8Vy9AgL8jifEHJ/RD
Y5xHyS8grddwWyxzTelOH5bIW7b9M9GpHQ7fH494ZR49FGlFDz5TAGc0fA3y03dy
0vZJDLS/4Q2xryrutVGxjn9lVO4T1wP9/QEe2Pfvihb3fmIoMvhXlA5qjFCSC3Lp
njP5qJOy3RrMtftSKENa7E8HzwKBgQD8JwBuIU+fuTTBBzjrnDaNxZeGhVGoq528
GbPOB9k3zp0mpQzzhkL265KWTuv/FsKPDm6Mp/ozN7PDSY6cczXIIiwfyLG5UG3y
1q8BwbOVe/ASxwWPr/4zpIv2l9qW/X2HTJ4MWvZgU3wqUslQvMxLIELC45TaIKX2
PK89ZzjcIwKBgDHWxm5m/1l1lTVknsnwkp1bDwPVUgfO+iiL6tiTRKSt+KZp5a8R
Hi7TfPK6hg5qSaBqMlUSb0/ecKLVQXJcWGh1Kf375UiC4GflSA1Zp6/L5nnkrdUs
c1w0XkPhckfPnnNyHbquc2p37DHsMPYTWRCmSwW4xeJIzyqa6TK8GZ/hAoGALssG
F2nzHs797Txr4b1xmkmq3vnqt4RxlzCl33wxYVvkagGDL8YgszXwVBh9Ty9oF6gz
98JMeijCIWGLJ5lxx5wf2B4kbSFx5fjVLVxG+VywpqtsasfcQrUsjCyOEiskmnEr
e1t+EU4s4qXOWj9PIjnwab5WJ0ybv+BvHNQFB0UCgYAfmi5OMthpQyXNfaapSJeh
BMwR/Md7iztCpR2ZvoAWn+JByKxnZlcieB8GyumwaESa3sDI0h29+pxmJ8Sm3wgn
W8zsOg1+i2ZcmQITKm+tPu3K+X4vJBm6ZYTjBgadvtSTMgpsolG9xi39BLqbLYGN
VvObHbGKtU6felU3KeWImw==
-----END PRIVATE KEY-----
PEM;

        $clerkPublicKey = <<<'PEM'
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA+IxEOD/by0EgQpdH0dkl
8TbQHuVTKudxQbUoQPc/yHtGyUa0lZFdp3x05ROdNtnje/woDWBbFPURUzwhgX85
sUKHWVKjA2DYlimX+dTmcrbo3vM5MJFLSHA9OeBciyyjgQqm/j43ixknBNqk2qjg
MSptG0R7L6LaUmtQCOieP5H8MTjoblmM4YJkQzapKzJO+v6rrlAUcXfnF0K3fD+1
6U95KHD0M3MsbQ6YPEznHYTJE00nbq8sFKi2BTQyNyDkSN2X+296rB5SaAdZiOfN
5PhFVJq8d+oqjMS4975zzsVPndFE0lidzBQYjHgWF1ZPxNxK+UefeIU6CjNDN8v1
TQIDAQAB
-----END PUBLIC KEY-----
PEM;

        config([
            'child_service.clerk_jwt_key' => $clerkPublicKey,
            'child_service.clerk_issuer' => 'https://example.clerk.accounts.dev',
            'child_service.clerk_authorized_parties' => ['https://literacy-app.test'],
        ]);
    }

    public function test_parent_can_create_child_with_generated_credentials(): void
    {
        $response = $this
            ->withToken($this->clerkToken('user_parent123'))
            ->postJson('/api/parents/children', [
                'first_name' => 'Lina',
                'last_name' => 'Reader',
                'age' => 8,
                'skill_level' => 'beginner',
                'subscription_id' => 'sub_123',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('child.parent_id', 'user_parent123')
            ->assertJsonPath('child.first_name', 'Lina')
            ->assertJsonStructure([
                'child' => ['id', 'parent_id', 'first_name', 'username', 'status'],
                'credentials' => ['username', 'pin'],
            ]);

        $child = Child::query()->firstOrFail();

        $this->assertSame($response->json('credentials.username'), $child->username);
        $this->assertTrue(Hash::check($response->json('credentials.pin'), $child->password));
        $this->assertMatchesRegularExpression('/^\d{6}$/', $response->json('credentials.pin'));
    }

    public function test_parent_can_create_and_update_child_with_base64_avatar(): void
    {
        $avatar = 'data:image/png;base64,'.str_repeat('a', 12000);
        $updatedAvatar = 'data:image/jpeg;base64,'.str_repeat('b', 14000);

        $create = $this
            ->withToken($this->clerkToken('user_parent123'))
            ->postJson('/api/parents/children', [
                'first_name' => 'Lina',
                'avatar' => $avatar,
            ]);

        $create
            ->assertCreated()
            ->assertJsonPath('child.avatar', $avatar);

        $child = Child::query()->firstOrFail();

        $this->assertSame($avatar, $child->avatar);

        $this
            ->withToken($this->clerkToken('user_parent123'))
            ->patchJson("/api/parents/children/{$child->id}", [
                'avatar' => $updatedAvatar,
            ])
            ->assertOk()
            ->assertJsonPath('avatar', $updatedAvatar);

        $this->assertSame($updatedAvatar, $child->refresh()->avatar);
    }

    public function test_parent_can_only_see_their_own_children(): void
    {
        Child::query()->create([
            'parent_id' => 'user_parent123',
            'first_name' => 'Own',
            'username' => 'own_child',
            'password' => '123456',
            'status' => 'active',
        ]);

        Child::query()->create([
            'parent_id' => 'user_parent456',
            'first_name' => 'Other',
            'username' => 'other_child',
            'password' => '123456',
            'status' => 'active',
        ]);

        $response = $this
            ->withToken($this->clerkToken('user_parent123'))
            ->getJson('/api/parents/children');

        $response
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.username', 'own_child');
    }

    public function test_child_can_login_and_read_profile_but_not_update_itself(): void
    {
        $child = Child::query()->create([
            'parent_id' => 'user_parent123',
            'first_name' => 'Lina',
            'username' => 'lina_reader',
            'password' => '123456',
            'status' => 'active',
        ]);

        $login = $this->postJson('/api/children/login', [
            'username' => 'lina_reader',
            'password' => '123456',
        ]);

        $login
            ->assertOk()
            ->assertJsonStructure(['access_token', 'token_type', 'expires_in', 'child']);

        $this
            ->withToken($login->json('access_token'))
            ->getJson('/api/children/me')
            ->assertOk()
            ->assertJsonPath('id', $child->id)
            ->assertJsonPath('username', 'lina_reader');

        $this
            ->withToken($login->json('access_token'))
            ->patchJson('/api/children/me', ['first_name' => 'Changed'])
            ->assertMethodNotAllowed();
    }

    public function test_parent_can_rotate_child_credentials(): void
    {
        $child = Child::query()->create([
            'parent_id' => 'user_parent123',
            'first_name' => 'Lina',
            'username' => 'lina_reader',
            'password' => '123456',
            'status' => 'active',
        ]);

        $response = $this
            ->withToken($this->clerkToken('user_parent123'))
            ->postJson("/api/parents/children/{$child->id}/credentials");

        $response
            ->assertOk()
            ->assertJsonPath('credentials.username', 'lina_reader');

        $child->refresh();

        $this->assertTrue(Hash::check($response->json('credentials.pin'), $child->password));
        $this->assertFalse(Hash::check('123456', $child->password));
    }

    private function clerkToken(string $clerkUserId): string
    {
        $now = time();

        return $this->rs256Token([
            'sub' => $clerkUserId,
            'iss' => 'https://example.clerk.accounts.dev',
            'azp' => 'https://literacy-app.test',
            'iat' => $now,
            'nbf' => $now - 10,
            'exp' => $now + 600,
            'sid' => 'sess_test',
        ]);
    }

    private function rs256Token(array $claims): string
    {
        $segments = [
            $this->base64UrlEncode(json_encode([
                'alg' => 'RS256',
                'typ' => 'JWT',
                'kid' => 'test-key',
            ], JSON_THROW_ON_ERROR)),
            $this->base64UrlEncode(json_encode($claims, JSON_THROW_ON_ERROR)),
        ];

        openssl_sign(implode('.', $segments), $signature, $this->clerkPrivateKey, OPENSSL_ALGO_SHA256);

        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
