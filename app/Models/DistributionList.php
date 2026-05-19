<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DistributionList extends Model
{
    protected $fillable = ['name', 'description'];

    public function recipients(): HasMany
    {
        return $this->hasMany(DistributionListRecipient::class)->orderBy('name');
    }

    public function eventTypes(): BelongsToMany
    {
        return $this->belongsToMany(EventType::class)->withTimestamps();
    }
}
