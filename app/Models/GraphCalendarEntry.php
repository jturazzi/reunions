<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GraphCalendarEntry extends Model
{
    protected $fillable = [
        'event_id',
        'recipient_email',
        'recipient_name',
        'graph_event_id',
        'status',
        'error_message',
        'synced_at',
    ];

    protected function casts(): array
    {
        return ['synced_at' => 'datetime'];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function isSynced(): bool
    {
        return $this->status === 'synced' && $this->graph_event_id !== null;
    }
}
