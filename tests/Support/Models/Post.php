<?php

namespace Tests\Support\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $guarded = [];

    function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
