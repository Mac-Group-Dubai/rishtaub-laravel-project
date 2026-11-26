<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\BlogAuthor;
use App\Models\Faq;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\Profile;
use App\Models\SuccessStory;
use App\Models\User;
use Tests\ApiTestCase;

class ContentTest extends ApiTestCase
{
    private function adminToken(): string
    {
        return User::factory()->admin()->create()->createToken('test')->plainTextToken;
    }

    private function userToken(): string
    {
        $user = User::factory()->create();
        $profile = new Profile();
        $profile->id = $user->id;
        $profile->email = $user->email;
        $profile->fill(['first_name' => 'Test', 'last_name' => 'User', 'gender' => 'male', 'dob' => '1995-01-01']);
        $profile->save();

        return $user->createToken('test')->plainTextToken;
    }

    private function makePlan(string $id, array $extra = []): Plan
    {
        return Plan::create(array_merge([
            'id' => $id, 'name' => ucfirst($id), 'price' => 0, 'duration' => '30 Days',
            'tagline' => 'Tagline', 'highlight' => false, 'sort_order' => 1, 'active' => true,
        ], $extra));
    }

    private function makeAuthor(): BlogAuthor
    {
        return BlogAuthor::create(['name' => 'Fatima Malik', 'title' => 'Counsellor', 'avatar' => 'https://example.com/a.jpg']);
    }

    private function makeBlog(BlogAuthor $author, array $extra = []): Blog
    {
        return Blog::create(array_merge([
            'slug' => 'first-post', 'title' => 'First post', 'excerpt' => 'Short', 'content' => '# Hello',
            'category' => 'Reflection', 'read_time' => '6 min read', 'published' => true,
            'published_at' => '2026-04-12 10:00:00', 'author_id' => $author->id,
            'author_name' => $author->name, 'author_avatar' => $author->avatar,
        ], $extra));
    }

    // ---------- public content ----------

    public function test_public_plans_show_active_plans_in_the_live_shape(): void
    {
        $this->makePlan('default');
        $this->makePlan('hidden', ['active' => false]);
        PlanFeature::create(['plan_id' => 'default', 'label' => '100 Express Interests', 'included' => true, 'sort_order' => 0]);

        $this->getJson('/api/v1/plans')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', 'default')
            ->assertJsonPath('data.0.features.0.label', '100 Express Interests')
            ->assertJsonPath('data.0.features.0.included', true)
            ->assertJsonMissingPath('data.0.active');

        $this->getJson('/api/v1/plans/default')->assertOk()->assertJsonPath('data.name', 'Default');
        $this->getJson('/api/v1/plans/hidden')->assertStatus(404)->assertJsonPath('message', 'Not found.');
    }

    public function test_public_blogs_list_only_published_and_show_works_by_slug(): void
    {
        $author = $this->makeAuthor();
        $this->makeBlog($author);
        $this->makeBlog($author, ['slug' => 'draft-post', 'title' => 'Draft', 'published' => false]);

        $this->getJson('/api/v1/blogs')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'first-post')
            ->assertJsonPath('data.0.date', 'April 12, 2026')
            ->assertJsonPath('data.0.readTime', '6 min read')
            ->assertJsonPath('data.0.author.name', 'Fatima Malik')
            ->assertJsonMissingPath('data.0.content');

        $this->getJson('/api/v1/blogs/first-post')
            ->assertOk()
            ->assertJsonPath('data.content', '# Hello')
            ->assertJsonPath('data.author.title', 'Counsellor');

        $this->getJson('/api/v1/blogs/draft-post')->assertStatus(404);
    }

    public function test_blogs_can_be_filtered_by_category_and_searched(): void
    {
        $author = $this->makeAuthor();
        $this->makeBlog($author);
        $this->makeBlog($author, ['slug' => 'other', 'title' => 'Wedding checklist', 'category' => 'Planning']);

        $this->getJson('/api/v1/blogs?category=Planning')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/blogs?q=checklist')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.slug', 'other');
    }

    public function test_public_success_stories_need_published_and_visible(): void
    {
        SuccessStory::create(['couple' => 'Fatima & Yusuf', 'quote' => 'Q', 'published' => true, 'visible' => true]);
        SuccessStory::create(['couple' => 'Hidden', 'quote' => 'Q', 'published' => true, 'visible' => false]);
        SuccessStory::create(['couple' => 'Draft', 'quote' => 'Q', 'published' => false, 'visible' => true]);

        $this->getJson('/api/v1/success-stories')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.couple', 'Fatima & Yusuf');
    }

    public function test_public_faqs_show_active_ones_and_filter_by_category(): void
    {
        Faq::create(['question' => 'Q1', 'answer' => 'A1', 'category' => 'General', 'sort_order' => 1, 'active' => true]);
        Faq::create(['question' => 'Q2', 'answer' => 'A2', 'category' => 'Billing', 'sort_order' => 2, 'active' => true]);
        Faq::create(['question' => 'Q3', 'answer' => 'A3', 'category' => 'General', 'sort_order' => 3, 'active' => false]);

        $this->getJson('/api/v1/faqs')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/faqs?category=General')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.question', 'Q1');
    }

    public function test_lists_are_paginated(): void
    {
        foreach ([1, 2, 3] as $i) {
            Faq::create(['question' => "Q{$i}", 'answer' => 'A', 'sort_order' => $i, 'active' => true]);
        }

        $this->getJson('/api/v1/faqs?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3);
    }

    // ---------- contact form ----------

    public function test_contact_form_saves_a_submission_like_the_live_page(): void
    {
        $this->postJson('/api/v1/contact-submissions', [
            'first_name' => 'Ali', 'last_name' => 'Khan', 'email' => 'ali@example.com',
            'subject' => 'Hello <script>x</script>', 'message' => 'I need help',
        ])
            ->assertCreated()
            ->assertJsonPath('message', "Message sent! We'll reply within 24 hours.");

        $this->assertDatabaseHas('contact_submissions', [
            'name' => 'Ali Khan', 'email' => 'ali@example.com', 'status' => 'unread', 'subject' => 'Hello x',
        ]);
    }

    public function test_contact_form_needs_email_and_message(): void
    {
        $this->postJson('/api/v1/contact-submissions', ['first_name' => 'Ali'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'message']);
    }

    // ---------- admin: who is allowed ----------

    public function test_admin_content_routes_are_closed_to_guests_and_normal_users(): void
    {
        $this->getJson('/api/v1/admin/plans')->assertStatus(401);
        $this->postJson('/api/v1/admin/faqs', ['question' => 'Q', 'answer' => 'A'])->assertStatus(401);

        $this->withToken($this->userToken())->getJson('/api/v1/admin/plans')->assertStatus(403);
        $this->withToken($this->userToken())->postJson('/api/v1/admin/faqs', ['question' => 'Q', 'answer' => 'A'])->assertStatus(403);
    }

    // ---------- admin: plans ----------

    public function test_admin_can_create_update_and_delete_a_plan_with_features(): void
    {
        $token = $this->adminToken();

        $this->withToken($token)->postJson('/api/v1/admin/plans', [
            'id' => 'gold', 'name' => 'Gold', 'price' => 49.5, 'duration' => '90 Days', 'tagline' => 'Best value',
            'features' => [['label' => '250 Express Interests'], ['label' => 'Priority support', 'included' => false]],
        ])
            ->assertCreated()
            ->assertJsonPath('data.id', 'gold')
            ->assertJsonCount(2, 'data.features')
            ->assertJsonPath('data.features.1.included', false);

        $this->withToken($token)->patchJson('/api/v1/admin/plans/gold', [
            'name' => 'Gold Plus', 'features' => [['label' => 'Only one']],
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Gold Plus')
            ->assertJsonCount(1, 'data.features');

        $this->withToken($token)->getJson('/api/v1/admin/plans?active=1')->assertOk()->assertJsonPath('data.0.active', true);

        $this->withToken($token)->deleteJson('/api/v1/admin/plans/gold')->assertNoContent();
        $this->assertDatabaseMissing('plans', ['id' => 'gold']);
        $this->assertDatabaseCount('plan_features', 0);

        // every admin change is written to audit_logs
        $this->assertDatabaseHas('audit_logs', ['action' => 'plan.create', 'table_name' => 'plans', 'record_id' => 'gold']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'plan.update']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'plan.delete']);
    }

    public function test_plan_validation_and_duplicate_id(): void
    {
        $token = $this->adminToken();
        $this->makePlan('gold');

        $this->withToken($token)->postJson('/api/v1/admin/plans', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['id', 'name', 'price', 'duration', 'tagline']);

        $this->withToken($token)->postJson('/api/v1/admin/plans', [
            'id' => 'gold', 'name' => 'Gold', 'price' => 1, 'duration' => 'x', 'tagline' => 'y',
        ])->assertStatus(422)->assertJsonValidationErrors(['id']);
    }

    // ---------- admin: blogs and authors ----------

    public function test_admin_can_manage_blogs_and_the_author_copy_stays_in_sync(): void
    {
        $token = $this->adminToken();
        $author = $this->makeAuthor();

        $created = $this->withToken($token)->postJson('/api/v1/admin/blogs', [
            'slug' => 'new-post', 'title' => 'New <b>post</b>', 'content' => 'Body', 'author_id' => $author->id,
            'published' => true,
        ])->assertCreated()->assertJsonPath('data.title', 'New post')->assertJsonPath('data.author.name', 'Fatima Malik');

        $id = $created->json('data.id');
        $this->assertDatabaseHas('blogs', ['id' => $id, 'author_name' => 'Fatima Malik']);

        // slug must stay unique
        $this->withToken($token)->postJson('/api/v1/admin/blogs', [
            'slug' => 'new-post', 'title' => 'Again', 'author_id' => $author->id,
        ])->assertStatus(422)->assertJsonValidationErrors(['slug']);

        $this->withToken($token)->patchJson("/api/v1/admin/blogs/{$id}", ['title' => 'Edited', 'published' => false])
            ->assertOk()->assertJsonPath('data.title', 'Edited')->assertJsonPath('data.published', false);

        // a draft is hidden from the public but still opens for the admin
        $this->getJson('/api/v1/blogs/new-post')->assertStatus(404);
        $this->withToken($token)->getJson('/api/v1/admin/blogs/new-post')->assertOk();

        $this->withToken($token)->deleteJson("/api/v1/admin/blogs/{$id}")->assertNoContent();
        $this->assertDatabaseHas('audit_logs', ['action' => 'blog.create']);
    }

    public function test_an_author_with_blogs_cannot_be_deleted(): void
    {
        $token = $this->adminToken();
        $author = $this->makeAuthor();
        $blog = $this->makeBlog($author);

        $this->withToken($token)->deleteJson("/api/v1/admin/blog-authors/{$author->id}")
            ->assertStatus(409);

        $blog->delete();

        $this->withToken($token)->deleteJson("/api/v1/admin/blog-authors/{$author->id}")->assertNoContent();
    }

    // ---------- admin: stories, faqs, contact inbox ----------

    public function test_admin_can_manage_stories_and_faqs(): void
    {
        $token = $this->adminToken();

        $story = $this->withToken($token)->postJson('/api/v1/admin/success-stories', [
            'couple' => 'Aisha & Omar', 'quote' => 'Great', 'location' => 'London, UK', 'visible' => true, 'published' => true,
        ])->assertCreated()->json('data.id');

        $this->withToken($token)->patchJson("/api/v1/admin/success-stories/{$story}", ['visible' => false])
            ->assertOk()->assertJsonPath('data.visible', false);
        $this->getJson('/api/v1/success-stories')->assertOk()->assertJsonCount(0, 'data');

        $faq = $this->withToken($token)->postJson('/api/v1/admin/faqs', [
            'question' => 'How does it work?', 'answer' => 'Easy.', 'category' => 'General',
        ])->assertCreated()->json('data.id');

        $this->withToken($token)->deleteJson("/api/v1/admin/faqs/{$faq}")->assertNoContent();
        $this->withToken($token)->deleteJson("/api/v1/admin/success-stories/{$story}")->assertNoContent();
    }

    public function test_admin_contact_inbox_lists_filters_and_updates_status(): void
    {
        $token = $this->adminToken();

        $this->postJson('/api/v1/contact-submissions', ['first_name' => 'Ali', 'email' => 'ali@example.com', 'message' => 'Hi']);
        $id = $this->withToken($token)->getJson('/api/v1/admin/contact-submissions?status=unread')
            ->assertOk()->assertJsonCount(1, 'data')->json('data.0.id');

        $this->withToken($token)->patchJson("/api/v1/admin/contact-submissions/{$id}", ['status' => 'banana'])
            ->assertStatus(422)->assertJsonValidationErrors(['status']);

        $this->withToken($token)->patchJson("/api/v1/admin/contact-submissions/{$id}", ['status' => 'replied'])
            ->assertOk()->assertJsonPath('data.status', 'replied');

        $this->withToken($token)->getJson('/api/v1/admin/contact-submissions?status=unread')->assertOk()->assertJsonCount(0, 'data');
        $this->withToken($token)->deleteJson("/api/v1/admin/contact-submissions/{$id}")->assertNoContent();
    }
}
