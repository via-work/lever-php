<?php

namespace ViaWork\LeverPhp\Tests;

use GrahamCampbell\GuzzleFactory\GuzzleFactory;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Spatie\GuzzleRateLimiterMiddleware\RateLimiterMiddleware;
use ViaWork\LeverPhp\DuplicateAggregatorMiddleware;
use ViaWork\LeverPhp\LeverPhp;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected $lever;

    protected $mockHandler;

    protected $container;

    const BACKOFF_TEST = 100;

    protected function setUp(): void
    {
        $this->mockHandler = new MockHandler();

        $this->container = [];

        $mock = HandlerStack::create($this->mockHandler);

        $mock->push(DuplicateAggregatorMiddleware::buildQuery());

        $mock->push(RateLimiterMiddleware::perSecond(1));

        $stack = GuzzleFactory::handler(self::BACKOFF_TEST, null, $mock);

        $stack->push(Middleware::history($this->container));


        $client = new Client([
            'base_uri' => '',
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'handler' => $stack,
        ]);

        $this->lever = new LeverPhp(null, $client, null);
    }
}
