<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Engine extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'engines';

    /**
     * @var string[]
     */
    protected $fillable = [
        'other_id',
        'automobile_id',
        'name',
        'specs'
    ];

    protected $casts = [
        'specs' => 'array'
    ];

    /**
     * @return BelongsTo
     */
    public function engines(): BelongsTo
    {
        return $this->belongsTo('App\Models\Automobile');
    }

}
