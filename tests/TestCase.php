<?php


namespace ViaWork\LeverPhp\Tests;


use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use ViaWork\LeverPhp\LeverPhp;
use function GuzzleHttp\Psr7\build_query;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected $lever;

    protected $mockHandler;

    protected $container;

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
