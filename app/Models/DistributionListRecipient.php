<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DistributionListRecipient extends Model
{
    protected $fillable = ['distribution_list_id', 'name', 'email'];

    public function distributionList(): BelongsTo
    {
        return $this->belongsTo(DistributionList::class);
    }
}
