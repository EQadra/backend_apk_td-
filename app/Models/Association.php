<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToUser;
use App\Models\Traits\HasProducts;


class Association extends Model
{
    use HasFactory, BelongsToUser, HasProducts;

        protected $fillable = [
            'user_id',
            'name',
            'description',
            'city',
            'address',
            'phone',
            'image',
            'website',
        ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function feedbacks()
    {
        return $this->morphMany(Feedback::class, 'feedbackable');
    }

  public function posts()
{
    return $this->morphMany(Post::class, 'postable');
}


    public function news()
{
    return $this->morphMany(News::class, 'newable');
}

}
