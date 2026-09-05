<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Conge extends Model
{
    protected $table = 'conges';
    protected $fillable = ['user_id','start','end'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
