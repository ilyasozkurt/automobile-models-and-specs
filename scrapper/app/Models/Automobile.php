<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Automobile extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'automobiles';

    /**
     * @var string[]
     */
    protected $fillable = [
        'url_hash',
        'url',
        'brand_id',
        'name',
        'description',
        'press_release',
        'photos',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'photos' => 'array',
    ];

    /**
     * @return HasMany
     */
    public function engines(): HasMany
    {
        return $this->hasMany('App\Models\Engine');
    }
}
