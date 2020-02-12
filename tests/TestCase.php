<?php

namespace ViaWork\LeverPhp\Tests;

use GuzzleHttp\Middleware;
use GuzzleHttp\HandlerStack;
use ViaWork\LeverPhp\LeverPhp;
use GuzzleHttp\Handler\MockHandler;
use GrahamCampbell\GuzzleFactory\GuzzleFactory;
use ViaWork\LeverPhp\DuplicateAggregatorMiddleware;

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
                'handler' => $stack
            ]
        );

        $this->lever = new LeverPhp(null, $client);
    }



}
