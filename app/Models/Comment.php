<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'commentable_id',
        'commentable_type',
        'content',
    ];

    /** USER RELATION */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** MAIN POLYMORPHIC RELATION */
    public function commentable()
    {
        return $this->morphTo();
    }

    /** APPENDED TYPE ATTRIBUTE */
    protected $appends = ['type'];

    public function getTypeAttribute()
    {
        return class_basename($this->commentable_type);
    }

    /**
     * OPTIONAL DIRECT RELATION SHORTCUTS
     * Allow calling $comment->post, $comment->product, etc.
     * Works only when commentable_type matches the model.
     */

    public function post()
    {
        return $this->belongsTo(Post::class, 'commentable_id')
                    ->where('commentable_type', Post::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'commentable_id')
                    ->where('commentable_type', Product::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'commentable_id')
                    ->where('commentable_type', Service::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class, 'commentable_id')
                    ->where('commentable_type', Shop::class);
    }

    public function lawyer()
    {
        return $this->belongsTo(Lawyer::class, 'commentable_id')
                    ->where('commentable_type', Lawyer::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'commentable_id')
                    ->where('commentable_type', Doctor::class);
    }

    public function association()
    {
        return $this->belongsTo(Association::class, 'commentable_id')
                    ->where('commentable_type', Association::class);
    }
}
