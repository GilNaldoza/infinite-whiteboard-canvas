<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Whiteboard extends Model
{
    protected $fillable = [
        'title',
        'canvas_data',
    ];
}
