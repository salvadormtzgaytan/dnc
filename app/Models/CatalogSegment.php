<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CatalogSegment extends Model
{
    use HasFactory;
    protected $fillable = ['name'];

    public function questions()
    {
        return $this->hasMany(Question::class, 'catalog_segment_id');
    }
}
