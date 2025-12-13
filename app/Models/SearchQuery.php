<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SearchQuery extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'query',
        'duration_ms',
        'performed_at',
    ];
}
