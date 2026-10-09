<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** Lien / document utile attaché à un projet ou une tâche. */
class Lien extends Model
{
    protected $table = 'liens';

    protected $fillable = ['lienable_type', 'lienable_id', 'libelle', 'url'];

    public function lienable(): MorphTo
    {
        return $this->morphTo();
    }
}
