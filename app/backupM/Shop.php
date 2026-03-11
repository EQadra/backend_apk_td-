<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    use HasFactory;

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

    public function products()
    {
        return $this->morphMany(Product::class, 'productable');
    }

    public function services()
    {
        return $this->morphMany(Service::class, 'serviceable');
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
