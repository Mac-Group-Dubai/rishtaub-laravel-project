<?php

namespace Database\Seeders;

use App\Models\Blog;
use App\Models\BlogAuthor;
use App\Models\Plan;
use App\Models\SuccessStory;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Puts the exact plans, blogs and success stories of the live site (src/data/mock.ts)
 * into MySQL. Safe to run again: rows are matched by plan id, blog slug and couple name,
 * so nothing is duplicated. Other rows in these tables are left alone.
 *
 * Run:  php artisan db:seed --class=LiveContentSeeder
 */
class LiveContentSeeder extends Seeder
{
    public function run(): void
    {
        $data = json_decode(
            file_get_contents(database_path('seeders/data/live_content.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        DB::transaction(function () use ($data) {
            $this->plans($data['plans']);
            $this->blogs($data['blogs']);
            $this->stories($data['stories']);
        });

        $this->command?->info('Live content is in: '
            . count($data['plans']) . ' plans, '
            . count($data['blogs']) . ' blogs, '
            . count($data['stories']) . ' success stories.');
    }

    private function plans(array $plans): void
    {
        foreach (array_values($plans) as $i => $p) {
            $plan = Plan::find($p['id']) ?? new Plan();
            $plan->fill([
                'id' => $p['id'],
                'name' => $p['name'],
                'price' => $p['price'],
                'duration' => $p['duration'],
                'tagline' => $p['tagline'],
                'highlight' => $p['highlight'] ?? false,
                'sort_order' => $i,
                'active' => true,
            ])->save();

            $plan->features()->delete();
            foreach (array_values($p['features']) as $j => $feature) {
                $plan->features()->create([
                    'label' => $feature['label'],
                    'included' => $feature['included'] ?? true,
                    'sort_order' => $j,
                ]);
            }
        }
    }

    private function blogs(array $blogs): void
    {
        foreach ($blogs as $b) {
            $author = BlogAuthor::firstOrNew(['name' => $b['author']['name']]);
            $author->fill([
                'name' => $b['author']['name'],
                'title' => $b['author']['title'],
                'avatar' => $b['author']['avatar'],
            ])->save();

            $blog = Blog::firstOrNew(['slug' => $b['slug']]);
            $blog->fill([
                'slug' => $b['slug'],
                'title' => $b['title'],
                'excerpt' => $b['excerpt'],
                'content' => $b['content'],
                'cover' => $b['cover'],
                'category' => $b['category'],
                'read_time' => $b['readTime'],
                'published' => true,
                'published_at' => Carbon::parse($b['date']),
                'author_id' => $author->id,
                'author_name' => $author->name,
                'author_avatar' => $author->avatar,
            ])->save();
        }
    }

    private function stories(array $stories): void
    {
        foreach (array_values($stories) as $i => $s) {
            $story = SuccessStory::firstOrNew(['couple' => $s['couple']]);
            $story->fill([
                'couple' => $s['couple'],
                'location' => $s['location'],
                'date' => $s['date'],
                'story_date' => $s['date'],
                'quote' => $s['quote'],
                'image' => $s['image'],
                'published' => true,
                'visible' => true,
                'sort_order' => $i,
            ])->save();
        }
    }
}
