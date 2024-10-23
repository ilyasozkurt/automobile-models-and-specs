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

    /**
     * @return array
     */
    public function toCsv(): array
    {
        return [
            'id' => $this->id,
            'other_id' => $this->other_id,
            'automobile_id' => $this->automobile_id,
            'name' => $this->name,
            'specs' => json_encode($this->specs),
        ];
    }

}
