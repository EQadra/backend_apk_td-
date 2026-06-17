<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToUser;
use App\Models\Traits\HasProducts;


class Shop extends Model
{
    use HasFactory, BelongsToUser, HasProducts;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'address',
        'city',
        'phone',
        'image',
        'schedule',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function feedbacks()
    {
        return $this->morphMany(Feedback::class, 'feedbackable');
    }

    public function services()
    {
        return $this->morphMany(Service::class, 'serviceable');
    }

    public function news()
    {
        return $this->morphMany(News::class, 'newable');
    }


public function posts()
{
    return $this->morphMany(Post::class, 'postable');
}

protected $appends = ['image_url'];

public function getImageUrlAttribute()
{
    if (!$this->image) {
        return null;
    }

    return asset('storage/' . $this->image);
}


    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
public function favorites()
{
    return $this->morphMany(Favorite::class, 'favoritable');
}

public function histories()
{
    return $this->morphMany(History::class, 'historyable');
}

}
