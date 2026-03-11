<?php
namespace Database\Seeders;


use App\Models\Comment;
use App\Models\Post;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;

class CommentSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        // Comments para posts
        Post::all()->each(function ($post) use ($users) {
            Comment::factory(rand(1, 5))->create([
                'user_id' => $users->random()->id,
                'commentable_id' => $post->id,
                'commentable_type' => Post::class,
            ]);
        });

        // Comments para productos
        Product::all()->each(function ($product) use ($users) {
            Comment::factory(rand(1, 5))->create([
                'user_id' => $users->random()->id,
                'commentable_id' => $product->id,
                'commentable_type' => Product::class,
            ]);
        });

        // Comments para servicios
        Service::all()->each(function ($service) use ($users) {
            Comment::factory(rand(1, 5))->create([
                'user_id' => $users->random()->id,
                'commentable_id' => $service->id,
                'commentable_type' => Service::class,
            ]);
        });
    }
}
