<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class EventType extends Model
{
    protected $fillable = ['name', 'slug', 'description'];

    protected static function booted(): void
    {
        static::creating(function (EventType $eventType) {
            if (empty($eventType->slug)) {
                $eventType->slug = Str::slug($eventType->name);
            }
        });
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function distributionLists(): BelongsToMany
    {
        return $this->belongsToMany(DistributionList::class)->withTimestamps();
    }
}
