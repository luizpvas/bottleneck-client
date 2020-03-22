<?php

namespace Tests;

class ContextTest extends TestCase
{
    /** @test */
    function adds_data_to_the_metrics_context()
    {
        $this->get('/posts')->assertStatus(200);

        bottleneck()->addContext('key', 'value');

        $this->assertEquals(['key' => 'value'], bottleneck()->toArray()['context']);
    }

    /** @test */
    function throws_an_exception_for_arrays_and_objects()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->get('/posts')->assertStatus(200);

        bottleneck()->addContext('key', [1, 2, 3]);
    }
}
