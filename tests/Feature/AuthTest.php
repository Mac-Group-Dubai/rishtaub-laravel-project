<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\ApiTestCase;

class AuthTest extends ApiTestCase
{
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'onbehalf' => 'self',
            'first_name' => 'Ali',
            'last_name' => 'Khan',
            'gender' => 'male',
            'country_code' => '+92',
            'phone' => '3001234567',
            'dob' => '1995-05-20',
            'email' => 'ali@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $overrides);
    }

    private function addProfile(User $user, array $profile = []): User
    {
        $row = new Profile();
        $row->id = $user->id;
        $row->email = $user->email;
        $row->fill(array_merge([
            'first_name' => 'Test',
            'last_name' => 'User',
            'gender' => 'male',
            'dob' => '1995-01-01',
        ], $profile));
        $row->save();

        return $user;
    }

    private function makeUser(array $profile = [], array $user = []): User
    {
        return $this->addProfile(User::factory()->create($user), $profile);
    }

    // ---------- register ----------

    public function test_register_creates_user_profile_quota_and_sends_verification_email(): void
    {
        Notification::fake();

        $this->postJson('/api/v1/auth/register', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.email', 'ali@example.com')
            ->assertJsonMissingPath('data.token');

        $user = User::where('email', 'ali@example.com')->firstOrFail();

        $this->assertSame('user', $user->role);
        $this->assertNull($user->email_verified_at);
        $this->assertNotSame('password123', $user->password);
        $this->assertDatabaseHas('profiles', [
            'id' => $user->id,
            'first_name' => 'Ali',
            'phone' => '+923001234567',
            'onbehalf' => 'self',
        ]);
        $this->assertDatabaseHas('user_quota', ['user_id' => $user->id]);

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_register_cannot_set_role(): void
    {
        Notification::fake();

        $this->postJson('/api/v1/auth/register', $this->payload(['role' => 'admin']))->assertCreated();

        $this->assertSame('user', User::where('email', 'ali@example.com')->value('role'));
    }

    public function test_register_validates_required_fields(): void
    {
        $this->postJson('/api/v1/auth/register', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'first_name', 'last_name', 'gender', 'country_code', 'phone', 'dob', 'email', 'password',
            ]);
    }

    public function test_register_rejects_duplicate_email(): void
    {
        $this->makeUser([], ['email' => 'ali@example.com']);

        $this->postJson('/api/v1/auth/register', $this->payload())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_register_rejects_gender_other_and_short_phone_and_password_mismatch(): void
    {
        $this->postJson('/api/v1/auth/register', $this->payload([
            'gender' => 'other',
            'phone' => '123',
            'password_confirmation' => 'different',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['gender', 'phone', 'password']);
    }

    public function test_register_strips_html_from_names(): void
    {
        Notification::fake();

        $this->postJson('/api/v1/auth/register', $this->payload(['first_name' => '<b>Ali</b>']))->assertCreated();

        $this->assertDatabaseHas('profiles', ['email' => 'ali@example.com', 'first_name' => 'Ali']);
    }

    public function test_register_returns_token_when_verification_is_turned_off(): void
    {
        config(['rishtahub.require_email_verification' => false]);

        $this->postJson('/api/v1/auth/register', $this->payload())
            ->assertCreated()
            ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'email', 'role', 'profile']]]);

        $this->assertNotNull(User::where('email', 'ali@example.com')->value('email_verified_at'));
    }

    // ---------- login ----------

    public function test_login_returns_token_and_profile(): void
    {
        $this->makeUser(['first_name' => 'Ayesha'], ['email' => 'ali@example.com']);

        $this->postJson('/api/v1/auth/login', ['email' => 'ali@example.com', 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('data.user.profile.first_name', 'Ayesha')
            ->assertJsonStructure(['data' => ['token']]);
    }

    public function test_login_with_wrong_password_is_401(): void
    {
        $this->makeUser([], ['email' => 'ali@example.com']);

        $this->postJson('/api/v1/auth/login', ['email' => 'ali@example.com', 'password' => 'wrong-password'])
            ->assertStatus(401)
            ->assertJsonPath('code', 'invalid_credentials');
    }

    public function test_login_is_refused_until_email_is_verified(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'ali@example.com']);
        $this->addProfile($user);

        $this->postJson('/api/v1/auth/login', ['email' => 'ali@example.com', 'password' => 'password'])
            ->assertStatus(403)
            ->assertJsonPath('code', 'email_not_verified');
    }

    public function test_login_is_refused_for_blocked_account(): void
    {
        $this->makeUser(['is_active' => false], ['email' => 'ali@example.com']);

        $this->postJson('/api/v1/auth/login', ['email' => 'ali@example.com', 'password' => 'password'])
            ->assertStatus(403)
            ->assertJsonPath('code', 'account_blocked');
    }

    public function test_admin_without_profile_can_login(): void
    {
        User::factory()->admin()->create(['email' => 'boss@example.com']);

        $this->postJson('/api/v1/auth/login', ['email' => 'boss@example.com', 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('data.user.role', 'admin');
    }

    // ---------- me / logout ----------

    public function test_me_requires_a_token(): void
    {
        $this->getJson('/api/v1/auth/me')->assertStatus(401);
    }

    public function test_me_returns_user_with_profile(): void
    {
        $user = $this->makeUser(['first_name' => 'Sara']);
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.profile.first_name', 'Sara');
    }

    public function test_logout_deletes_the_token(): void
    {
        $user = $this->makeUser();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_token_of_a_blocked_account_stops_working(): void
    {
        $user = $this->makeUser(['is_active' => false]);
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/auth/me')
            ->assertStatus(403)
            ->assertJsonPath('code', 'account_blocked');
    }

    // ---------- roles ----------

    public function test_admin_route_is_closed_to_guests_and_users_but_open_to_admins(): void
    {
        $this->getJson('/api/v1/admin/ping')->assertStatus(401);

        $user = $this->makeUser();
        $this->withToken($user->createToken('test')->plainTextToken)
            ->getJson('/api/v1/admin/ping')
            ->assertStatus(403);

        $admin = User::factory()->admin()->create();
        $this->withToken($admin->createToken('test')->plainTextToken)
            ->getJson('/api/v1/admin/ping')
            ->assertOk()
            ->assertJsonPath('message', 'admin ok');
    }

    // ---------- email verification ----------

    public function test_verification_link_verifies_the_email_and_redirects(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->get($url)->assertRedirect();

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_resend_always_answers_ok_and_mails_unverified_users(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create(['email' => 'ali@example.com']);

        $this->postJson('/api/v1/auth/email/resend', ['email' => 'nobody@example.com'])->assertOk();
        $this->postJson('/api/v1/auth/email/resend', ['email' => 'ali@example.com'])->assertOk();

        Notification::assertSentTo($user, VerifyEmail::class);
    }
}
