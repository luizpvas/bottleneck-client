<?php

namespace Tests;

use Illuminate\Support\Facades\View;
use Bottleneck\BottleneckClientServiceProvider;
use Tests\Support\Controllers\PostsController;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Tests\Support\Controllers\ErrorController;
use Tests\Support\Models\Post;
use Tests\Support\Models\User;

class TestCase extends OrchestraTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom([
            '--path' => realpath(__DIR__ . '/migrations')
        ]);

        View::addLocation(__DIR__ . '/views');

        $luiz = User::create([
            'name' => 'Luiz',
            'email' => 'luiz@teclia.com'
        ]);

        Post::create([
            'author_id' => $luiz->id,
            'title' => 'Green field',
            'body' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.'
        ]);

        Post::create([
            'author_id' => $luiz->id,
            'title' => 'Blue field',
            'body' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.'
        ]);
    }

    protected function getPackageProviders($app)
    {
        return [BottleneckClientServiceProvider::class];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $app['router']->resource('posts', PostsController::class)->middleware([\Bottleneck\Middleware::class]);
        $app['router']->resource('errors', ErrorController::class)->middleware([\Bottleneck\Middleware::class]);
    }

    /**
     * Resolve application HTTP exception handler.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return void
     */
    protected function resolveApplicationExceptionHandler($app)
    {
        $app->singleton(
            'Illuminate\Contracts\Debug\ExceptionHandler',
            \Tests\Support\ExceptionHandler::class
        );
    }
}
