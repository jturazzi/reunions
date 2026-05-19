<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $upcomingEvents = Event::with('eventType')
            ->where('start_at', '>=', now()->startOfDay())
            ->orderBy('start_at')
            ->get()
            ->map(function ($event) {
                $today     = now()->startOfDay();
                $startDay  = $event->start_at->copy()->startOfDay();
                $daysUntil = (int) $today->diffInDays($startDay, false);

                return [
                    'id'          => $event->id,
                    'title'       => $event->title,
                    'description' => $event->description,
                    'location'    => $event->location,
                    'month'       => $event->start_at->translatedFormat('M'),
                    'day'         => $event->start_at->format('d'),
                    'year'        => $event->start_at->format('Y'),
                    'days_until'  => $daysUntil,
                    'time_range'  => $event->start_at->format('H\\hi') . ' → ' . $event->end_at->format('H\\hi'),
                    'event_type'  => ['name' => $event->eventType->name],
                ];
            });

        return Inertia::render('Dashboard', [
            'upcomingEvents' => $upcomingEvents,
        ]);
    }
}
