<?php

namespace ViaWork\LeverPhp\Tests;

use GrahamCampbell\GuzzleFactory\GuzzleFactory;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use ViaWork\LeverPhp\DuplicateAggregatorMiddleware;
use ViaWork\LeverPhp\LeverPhp;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected $lever;

    protected $mockHandler;

    protected $container;

    protected function setUp(): void
    {
        $this->mockHandler = new MockHandler();

        $this->container = [];

        $stack = HandlerStack::create($this->mockHandler);

        $stack->push(DuplicateAggregatorMiddleware::buildQuery());

        $stack->push(Middleware::history($this->container));

        $client = GuzzleFactory::make(
            [
                'base_uri' => '',
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'handler' => $stack,
            ]
        );

        $this->lever = new LeverPhp(null, $client);
    }
}
