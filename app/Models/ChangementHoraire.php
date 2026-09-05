<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChangementHoraire extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type_chgmt',
        'old_start',
        'old_end',
        'new_start',
        'new_end',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
