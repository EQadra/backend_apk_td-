<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Association extends Model
{
    use HasFactory;

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

  public function products()
    {
        return $this->morphMany(Product::class, 'productable');
    }


    public function news()
{
    return $this->morphMany(News::class, 'newable');
}

    // 🔥 Renombrada para evitar choque con atributo "services"
    public function serviceItems()
    {
        return $this->morphMany(Service::class, 'serviceable');
    }


}
