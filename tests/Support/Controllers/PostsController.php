<?php

namespace Tests\Support\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Tests\Support\Models\Post;

class PostsController extends Controller
{
    function index()
    {
        $posts = Post::all();
        usleep(100000); // 100ms

        $miss = cache('key');
        cache(['key' => 'value']);
        $hit = cache('key');

        return view('posts.index', compact('posts'));
    }
}
