<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_blocks_sixth_attempt_and_allows_retry_after_expiry(): void
    {
        $user = User::factory()->create(['password' => bcrypt('correct-password')]);
        $payload = ['email' => $user->email, 'password' => 'incorrect-password'];
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/auth/login', $payload)->assertForbidden();
        }

        $payload['password'] = 'correct-password';
        $blocked = $this->postJson('/api/auth/login', $payload)
            ->assertStatus(429)->assertHeader('Retry-After')
            ->assertJsonMissingPath('access_token');
        $seconds = $blocked->json('retry_after');
        $this->assertGreaterThan(0, $seconds);
        $this->assertSame((string) $seconds, $blocked->headers->get('Retry-After'));
        $blocked->assertJsonPath('message', "Muitas tentativas. Aguarde {$seconds} segundos e tente novamente.");

        $this->travel(61)->seconds();
        $this->postJson('/api/auth/login', $payload)->assertOk()->assertJsonStructure(['access_token']);
    }

    public function test_email_case_and_whitespace_do_not_bypass_limit(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/auth/login', ['email' => 'unknown@example.test', 'password' => 'wrong'])
                ->assertForbidden();
        }
        $this->postJson('/api/auth/login', ['email' => ' UNKNOWN@EXAMPLE.TEST ', 'password' => 'wrong'])
            ->assertStatus(429);
    }

    public function test_identity_limit_does_not_block_other_accounts_or_ips(): void
    {
        $payload = ['email' => 'unknown@example.test', 'password' => 'wrong'];
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/auth/login', $payload)->assertForbidden();
        }
        $this->postJson('/api/auth/login', ['email' => 'another@example.test', 'password' => 'wrong'])
            ->assertForbidden();
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.2'])
            ->postJson('/api/auth/login', $payload)->assertForbidden();
    }

    public function test_rotating_emails_and_forwarded_headers_does_not_bypass_ip_limit(): void
    {
        for ($attempt = 0; $attempt < 60; $attempt++) {
            // Invalid payloads avoid password work but still consume the IP budget.
            $this->withHeader('X-Forwarded-For', '192.0.2.'.($attempt + 1))
                ->postJson('/api/auth/login', ['email' => "user{$attempt}@example.test"])
                ->assertUnprocessable();
        }
        $this->postJson('/api/auth/login', ['email' => 'fresh@example.test'])->assertStatus(429);
    }

    public function test_malformed_email_is_validated_and_rate_limited_without_server_error(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/auth/login', ['email' => ['invalid'], 'password' => 'wrong'])
                ->assertUnprocessable();
        }
        $this->postJson('/api/auth/login', [])->assertStatus(429);
    }

    public function test_registration_has_separate_ip_budget_and_recovers_after_expiry(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/auth/register', [])->assertUnprocessable();
        }
        $this->postJson('/api/auth/register', [])->assertStatus(429)->assertHeader('Retry-After');
        $this->postJson('/api/auth/login', [])->assertUnprocessable();
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.2'])
            ->postJson('/api/auth/register', [])->assertUnprocessable();
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1']);
        $this->travel(61)->seconds();
        $this->postJson('/api/auth/register', [])->assertUnprocessable();
    }
}
