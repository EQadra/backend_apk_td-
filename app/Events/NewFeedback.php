<?php
// app/Events/NewFeedback.php
namespace App\Events;

use App\Models\Feedback;
use App\Models\Shop;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewFeedback implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $feedback;

    public function __construct(Feedback $feedback)
    {
        $this->feedback = $feedback;
    }

    public function broadcastOn(): Channel
    {
        // Asume que Feedback tiene relación con Shop
        return new PrivateChannel('shop.' . $this->feedback->feedbackable_id);
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->feedback->id,
            'rating' => $this->feedback->rating,
            'comment' => $this->feedback->comment,
            'user' => $this->feedback->user->name,
            'created_at' => $this->feedback->created_at->toISOString(),
        ];
    }
}