<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The live Supabase schema has unique pairs, self-reference checks and indexes
 * that were not in the first export. This adds them to MySQL (no data is changed).
 */
return new class extends Migration
{
    public function up(): void
    {
        // One row per pair, exactly like the live UNIQUE constraints
        Schema::table('interests', fn (Blueprint $t) => $t->unique(['sender_id', 'receiver_id']));
        Schema::table('shortlists', fn (Blueprint $t) => $t->unique(['owner_id', 'target_id']));
        Schema::table('ignored_profiles', fn (Blueprint $t) => $t->unique(['owner_id', 'target_id']));
        Schema::table('user_blocks', fn (Blueprint $t) => $t->unique(['blocker_id', 'blocked_id']));

        // Live CHECK constraints
        DB::statement('ALTER TABLE interests ADD CONSTRAINT no_self_interest CHECK (sender_id <> receiver_id)');
        DB::statement('ALTER TABLE user_blocks ADD CONSTRAINT no_self_block CHECK (blocker_id <> blocked_id)');
        DB::statement('ALTER TABLE chat_reports ADD CONSTRAINT no_self_report CHECK (reporter_id <> reported_user_id)');
        DB::statement('ALTER TABLE messages ADD CONSTRAINT messages_body_not_blank CHECK (CHAR_LENGTH(TRIM(body)) > 0)');
        DB::statement('ALTER TABLE chat_reports ADD CONSTRAINT chat_reports_reason_not_blank CHECK (CHAR_LENGTH(TRIM(reason)) > 0)');
        DB::statement("ALTER TABLE contact_submissions ADD CONSTRAINT contact_submissions_status_check CHECK (status IN ('unread', 'read', 'replied'))");

        // Live indexes
        Schema::table('profiles', function (Blueprint $t) {
            $t->index(['gender', 'dob'], 'idx_profiles_gender_dob');
            $t->index(['religion', 'caste'], 'idx_profiles_religion_caste');
            $t->index(['country', 'city'], 'idx_profiles_country_city');
        });
        Schema::table('interests', function (Blueprint $t) {
            $t->index(['receiver_id', 'status', 'created_at'], 'idx_interests_receiver_status');
            $t->index(['sender_id', 'created_at'], 'idx_interests_sender');
        });
        Schema::table('profile_views', fn (Blueprint $t) => $t->index(['viewed_id', 'viewed_at'], 'idx_profile_views_viewed'));
        Schema::table('messages', fn (Blueprint $t) => $t->index(['conversation_id', 'created_at'], 'idx_messages_conversation_created_at'));
        Schema::table('conversation_participants', fn (Blueprint $t) => $t->index(['user_id', 'conversation_id'], 'idx_conversation_participants_user'));
        Schema::table('chat_reports', fn (Blueprint $t) => $t->index(['conversation_id', 'created_at'], 'idx_chat_reports_conversation'));
        Schema::table('blogs', function (Blueprint $t) {
            $t->index('category', 'idx_blogs_category');
            $t->index(['published', 'published_at'], 'idx_blogs_published');
        });
    }

    public function down(): void
    {
        Schema::table('blogs', function (Blueprint $t) {
            $t->dropIndex('idx_blogs_category');
            $t->dropIndex('idx_blogs_published');
        });
        Schema::table('chat_reports', fn (Blueprint $t) => $t->dropIndex('idx_chat_reports_conversation'));
        Schema::table('conversation_participants', fn (Blueprint $t) => $t->dropIndex('idx_conversation_participants_user'));
        Schema::table('messages', fn (Blueprint $t) => $t->dropIndex('idx_messages_conversation_created_at'));
        Schema::table('profile_views', fn (Blueprint $t) => $t->dropIndex('idx_profile_views_viewed'));
        Schema::table('interests', function (Blueprint $t) {
            $t->dropIndex('idx_interests_receiver_status');
            $t->dropIndex('idx_interests_sender');
        });
        Schema::table('profiles', function (Blueprint $t) {
            $t->dropIndex('idx_profiles_gender_dob');
            $t->dropIndex('idx_profiles_religion_caste');
            $t->dropIndex('idx_profiles_country_city');
        });

        DB::statement('ALTER TABLE contact_submissions DROP CHECK contact_submissions_status_check');
        DB::statement('ALTER TABLE chat_reports DROP CHECK chat_reports_reason_not_blank');
        DB::statement('ALTER TABLE messages DROP CHECK messages_body_not_blank');
        DB::statement('ALTER TABLE chat_reports DROP CHECK no_self_report');
        DB::statement('ALTER TABLE user_blocks DROP CHECK no_self_block');
        DB::statement('ALTER TABLE interests DROP CHECK no_self_interest');

        Schema::table('user_blocks', fn (Blueprint $t) => $t->dropUnique(['blocker_id', 'blocked_id']));
        Schema::table('ignored_profiles', fn (Blueprint $t) => $t->dropUnique(['owner_id', 'target_id']));
        Schema::table('shortlists', fn (Blueprint $t) => $t->dropUnique(['owner_id', 'target_id']));
        Schema::table('interests', fn (Blueprint $t) => $t->dropUnique(['sender_id', 'receiver_id']));
    }
};
