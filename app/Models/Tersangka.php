<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tersangka extends Model
{

    use HasFactory;
    protected $table = 'tersangkas';

    protected $fillable = [
        'perkara_id',
        'nama',
    ];

    public function perkara(): BelongsTo
    {
        return $this->belongsTo(Perkara::class);
    }
};
