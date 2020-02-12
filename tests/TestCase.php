<?php


namespace ViaWork\LeverPhp\Tests;


use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use ViaWork\LeverPhp\LeverPhp;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected LeverPhp $lever;

    protected MockHandler $mockHandler;

    protected array $container;

    protected function setUp(): void
    {
        $this->mockHandler = new MockHandler();

        $this->container = [];

        $history = Middleware::history($this->container);

        $stack = HandlerStack::create($this->mockHandler);

        $stack->push($history);

        $client = new Client([
            'handler' => $stack,
        ]);

        $this->lever = new LeverPhp(null, $client);

    }
}
