<?php

namespace App\Observers;

use App\Jobs\SyncGraphCalendarJob;
use App\Models\Event;

class EventObserver
{
    public function created(Event $event): void
    {
        SyncGraphCalendarJob::dispatch($event->id, 'create');
    }

    public function updated(Event $event): void
    {
        $calendarFields = ['title', 'description', 'start_at', 'end_at', 'location'];

        if (! $event->wasChanged($calendarFields)) {
            return;
        }

        SyncGraphCalendarJob::dispatch($event->id, 'update');
    }

    public function deleting(Event $event): void
    {
        SyncGraphCalendarJob::dispatch($event->id, 'delete');
    }
}
