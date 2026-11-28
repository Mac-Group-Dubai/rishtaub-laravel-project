<?php

namespace Tests\Feature;

use App\Models\IgnoredProfile;
use App\Models\Profile;
use App\Models\ProfileView;
use App\Models\Shortlist;
use App\Models\User;
use App\Models\UserQuota;
use Tests\ApiTestCase;

class ProfilesTest extends ApiTestCase
{
    private function makeMember(array $profile = [], array $user = []): User
    {
        $u = User::factory()->create($user);

        $p = new Profile();
        $p->id = $u->id;
        $p->email = $u->email;
        $p->fill(array_merge([
            'first_name' => 'Test', 'last_name' => 'Member', 'gender' => 'male',
            'dob' => now()->subYears(30)->toDateString(), 'phone' => '+923000000000',
        ], $profile));
        $p->save();

        UserQuota::create(['user_id' => $u->id, 'quota_reset_at' => now()->addDays(30)]);

        return $u;
    }

    private function makeAdmin(): User
    {
        return User::factory()->admin()->create();
    }

    // Sends the next request as this user (the test app remembers the previous user, so reset it)
    private function as(User $user)
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($user->createToken('test')->plainTextToken);
    }

    // ---------- login needed ----------

    public function test_profile_routes_need_a_login(): void
    {
        $this->getJson('/api/v1/profiles')->assertStatus(401);
        $this->getJson('/api/v1/profiles/me')->assertStatus(401);
        $this->getJson('/api/v1/shortlists')->assertStatus(401);
        $this->postJson('/api/v1/profile-views', [])->assertStatus(401);
    }

    // ---------- search ----------

    public function test_search_shows_only_active_members_and_hides_email_and_phone(): void
    {
        $me = $this->makeMember();
        $this->makeMember(['first_name' => 'Ayesha', 'gender' => 'female', 'city' => 'Karachi', 'religion' => 'Muslim',
            'dob' => now()->subYears(26)->toDateString()]);
        $this->makeMember(['first_name' => 'Sara', 'gender' => 'female', 'city' => 'Lahore',
            'dob' => now()->subYears(40)->toDateString()]);
        $this->makeMember(['first_name' => 'Blocked', 'gender' => 'female', 'city' => 'Karachi', 'is_active' => false]);

        $this->as($me)->getJson('/api/v1/profiles?gender=female')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonMissingPath('data.0.email')
            ->assertJsonMissingPath('data.0.phone');

        $this->as($me)->getJson('/api/v1/profiles?gender=female&city=kara')->assertOk()->assertJsonCount(1, 'data');
        $this->as($me)->getJson('/api/v1/profiles?religion=musl')->assertOk()->assertJsonCount(1, 'data');
        $this->as($me)->getJson('/api/v1/profiles?age_from=20&age_to=30&gender=female')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.first_name', 'Ayesha');
        $this->as($me)->getJson('/api/v1/profiles?q=ayesha')->assertOk()->assertJsonCount(1, 'data');
        $this->as($me)->getJson('/api/v1/profiles?gender=female&per_page=1')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('meta.per_page', 1)->assertJsonPath('meta.total', 2);
    }

    public function test_search_rejects_bad_filters(): void
    {
        $me = $this->makeMember();

        $this->as($me)->getJson('/api/v1/profiles?gender=robot&per_page=1000')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['gender', 'per_page']);
    }

    // ---------- one profile ----------

    public function test_other_profiles_hide_contact_details_but_my_own_shows_them(): void
    {
        $me = $this->makeMember();
        $other = $this->makeMember(['first_name' => 'Ayesha', 'gender' => 'female']);
        $blocked = $this->makeMember(['is_active' => false]);

        $this->as($me)->getJson("/api/v1/profiles/{$other->id}")
            ->assertOk()
            ->assertJsonPath('data.first_name', 'Ayesha')
            ->assertJsonMissingPath('data.phone')
            ->assertJsonMissingPath('data.email');

        $this->as($me)->getJson("/api/v1/profiles/{$me->id}")
            ->assertOk()
            ->assertJsonPath('data.email', $me->email)
            ->assertJsonPath('data.phone', '+923000000000');

        $this->as($me)->getJson("/api/v1/profiles/{$blocked->id}")->assertStatus(404);
    }

    // ---------- my profile ----------

    public function test_member_can_edit_only_the_allowed_fields_of_their_own_profile(): void
    {
        $me = $this->makeMember();

        $this->as($me)->getJson('/api/v1/profiles/me')->assertOk()->assertJsonPath('data.first_name', 'Test');

        $this->as($me)->patchJson('/api/v1/profiles/me', [
            'city' => 'Dubai',
            'about' => 'Hi <b>there</b>',
            'is_active' => false,
            'plan' => 'premium',
            'email' => 'hacker@example.com',
        ])
            ->assertOk()
            ->assertJsonPath('data.city', 'Dubai')
            ->assertJsonPath('data.about', 'Hi there');

        // fields a member must not change were ignored
        $fresh = Profile::find($me->id);
        $this->assertTrue($fresh->is_active);
        $this->assertSame('free', $fresh->plan);
        $this->assertSame($me->email, $fresh->email);
    }

    public function test_profile_update_is_validated(): void
    {
        $me = $this->makeMember();

        $this->as($me)->patchJson('/api/v1/profiles/me', ['gender' => 'robot', 'dob' => now()->addYear()->toDateString()])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['gender', 'dob']);
    }

    public function test_member_can_switch_plan(): void
    {
        $me = $this->makeMember();

        $this->as($me)->patchJson('/api/v1/profiles/me/plan', ['plan' => 'premium'])
            ->assertOk()->assertJsonPath('data.plan', 'premium');
        $this->assertNotNull(Profile::find($me->id)->plan_activated_at);

        $this->as($me)->patchJson('/api/v1/profiles/me/plan', ['plan' => 'gold'])
            ->assertStatus(422)->assertJsonValidationErrors(['plan']);
    }

    // ---------- shortlist and ignored ----------

    public function test_shortlist_add_list_and_remove(): void
    {
        $me = $this->makeMember();
        $target = $this->makeMember(['first_name' => 'Ayesha', 'gender' => 'female']);
        $blocked = $this->makeMember(['is_active' => false]);

        $this->as($me)->postJson('/api/v1/shortlists', ['target_id' => $target->id])->assertCreated();
        $this->as($me)->postJson('/api/v1/shortlists', ['target_id' => $target->id])->assertStatus(409);
        $this->as($me)->postJson('/api/v1/shortlists', ['target_id' => $me->id])
            ->assertStatus(422)->assertJsonValidationErrors(['target_id']);
        $this->as($me)->postJson('/api/v1/shortlists', ['target_id' => $blocked->id])
            ->assertStatus(422)->assertJsonValidationErrors(['target_id']);

        $this->as($me)->getJson('/api/v1/shortlists')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $target->id)
            ->assertJsonMissingPath('data.0.phone');

        // another member sees only their own list
        $this->as($target)->getJson('/api/v1/shortlists')->assertOk()->assertJsonCount(0, 'data');

        $this->as($me)->deleteJson("/api/v1/shortlists/{$target->id}")->assertNoContent();
        $this->as($me)->deleteJson("/api/v1/shortlists/{$target->id}")->assertStatus(404);
    }

    public function test_ignored_profiles_add_list_and_remove(): void
    {
        $me = $this->makeMember();
        $target = $this->makeMember(['gender' => 'female']);

        $this->as($me)->postJson('/api/v1/ignored-profiles', ['target_id' => $target->id])->assertCreated();
        $this->as($me)->postJson('/api/v1/ignored-profiles', ['target_id' => $target->id])->assertStatus(409);
        $this->as($me)->getJson('/api/v1/ignored-profiles')->assertOk()->assertJsonCount(1, 'data');
        $this->as($me)->deleteJson("/api/v1/ignored-profiles/{$target->id}")->assertNoContent();
        $this->as($me)->getJson('/api/v1/ignored-profiles')->assertOk()->assertJsonCount(0, 'data');
    }

    // ---------- profile views ----------

    public function test_profile_views_are_logged_and_the_owner_can_see_who_viewed(): void
    {
        $me = $this->makeMember(['first_name' => 'Viewer']);
        $target = $this->makeMember(['gender' => 'female']);

        $this->as($me)->postJson('/api/v1/profile-views', ['viewed_id' => $target->id])->assertCreated();
        $this->as($me)->postJson('/api/v1/profile-views', ['viewed_id' => $me->id])->assertNoContent(); // own profile is not logged
        $this->assertDatabaseCount('profile_views', 1);

        $this->as($target)->getJson('/api/v1/profile-views')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $me->id)
            ->assertJsonPath('data.0.first_name', 'Viewer')
            ->assertJsonStructure(['data' => [['viewed_at']]])
            ->assertJsonMissingPath('data.0.phone');

        $this->as($me)->getJson('/api/v1/profile-views')->assertOk()->assertJsonCount(0, 'data');
    }

    // ---------- quota ----------

    public function test_member_can_read_their_quota_with_the_live_defaults(): void
    {
        $me = $this->makeMember();

        $this->as($me)->getJson('/api/v1/user-quota')
            ->assertOk()
            ->assertJsonPath('data.interests_remaining', 100)
            ->assertJsonPath('data.contact_views_remaining', 10)
            ->assertJsonPath('data.gallery_uploads_remaining', 10);

        // members can read the quota but not change it
        $this->as($me)->patchJson('/api/v1/user-quota', ['interests_remaining' => 9999])->assertStatus(405);
    }

    // ---------- admin ----------

    public function test_admin_profile_routes_are_closed_to_guests_and_members(): void
    {
        $this->getJson('/api/v1/admin/profiles')->assertStatus(401);
        $this->as($this->makeMember())->getJson('/api/v1/admin/profiles')->assertStatus(403);
    }

    public function test_admin_sees_blocked_members_and_can_block_and_change_plan(): void
    {
        $admin = $this->makeAdmin();
        $member = $this->makeMember(['first_name' => 'Ayesha']);
        $this->makeMember(['first_name' => 'Hidden', 'is_active' => false]);
        $memberToken = $member->createToken('old')->plainTextToken;

        $this->as($admin)->getJson('/api/v1/admin/profiles')->assertOk()->assertJsonCount(2, 'data');
        $this->as($admin)->getJson('/api/v1/admin/profiles?is_active=0')->assertOk()->assertJsonCount(1, 'data');
        $this->as($admin)->getJson("/api/v1/admin/profiles/{$member->id}")
            ->assertOk()->assertJsonPath('data.phone', '+923000000000');

        $this->as($admin)->patchJson("/api/v1/admin/profiles/{$member->id}", ['plan' => 'premium'])
            ->assertOk()->assertJsonPath('data.plan', 'premium');

        // blocking signs the member out everywhere
        $this->as($admin)->patchJson("/api/v1/admin/profiles/{$member->id}", ['is_active' => false])
            ->assertOk()->assertJsonPath('data.is_active', false);

        $this->assertSame(0, $member->tokens()->count());
        $this->app['auth']->forgetGuards();
        $this->withToken($memberToken)->getJson('/api/v1/profiles/me')->assertStatus(401);

        $this->assertDatabaseHas('audit_logs', ['action' => 'profile.update', 'table_name' => 'profiles', 'record_id' => $member->id]);
    }

    public function test_admin_can_change_a_member_quota(): void
    {
        $admin = $this->makeAdmin();
        $member = $this->makeMember();

        $this->as($admin)->patchJson("/api/v1/admin/user-quota/{$member->id}", ['interests_remaining' => 5])
            ->assertOk()->assertJsonPath('data.interests_remaining', 5);
        $this->assertDatabaseHas('user_quota', ['user_id' => $member->id, 'interests_remaining' => 5]);

        $this->as($admin)->patchJson("/api/v1/admin/user-quota/{$member->id}", ['interests_remaining' => -1])
            ->assertStatus(422)->assertJsonValidationErrors(['interests_remaining']);

        $this->as($admin)->getJson("/api/v1/admin/user-quota?user_id={$member->id}")
            ->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_admin_can_list_what_members_did(): void
    {
        $admin = $this->makeAdmin();
        $a = $this->makeMember();
        $b = $this->makeMember(['gender' => 'female']);

        Shortlist::create(['owner_id' => $a->id, 'target_id' => $b->id]);
        IgnoredProfile::create(['owner_id' => $b->id, 'target_id' => $a->id]);
        ProfileView::create(['viewer_id' => $a->id, 'viewed_id' => $b->id, 'viewed_at' => now()]);

        $this->as($admin)->getJson("/api/v1/admin/shortlists?owner_id={$a->id}")->assertOk()->assertJsonCount(1, 'data');
        $this->as($admin)->getJson("/api/v1/admin/ignored-profiles?owner_id={$b->id}")->assertOk()->assertJsonCount(1, 'data');
        $this->as($admin)->getJson("/api/v1/admin/profile-views?viewed_id={$b->id}")->assertOk()->assertJsonCount(1, 'data');
    }
}
