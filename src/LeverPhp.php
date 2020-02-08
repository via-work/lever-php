<?php

namespace ViaWork\LeverPhp;

use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Collection;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\ClientException;
use GrahamCampbell\GuzzleFactory\GuzzleFactory;

class LeverPhp
{
    /** @var string */
    private $leverKey;

    /** @var GuzzleClient */
    private $client;

    /** @var string */
    private $queryParameters = '';

    /**
     * LeverPhp constructor.
     * @param string $leverKey
     * @param GuzzleClient|null $client
     */
    public function __construct(string $leverKey, GuzzleClient $client = null)
    {
        $this->leverKey = $leverKey;

        // TODO pass RateLimiterMiddleware, check if compatible with exponential backoff
        $this->client = $client ?? GuzzleFactory::make(
                [
                    'base_uri' => 'https://api.lever.co/v1/',
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ],
                    'auth' => [$leverKey, '']
                ]
            );
    }

    private function post(string $endpoint, $body = ''): ResponseInterface
    {
        try {
            $response = $this->client->post($endpoint, [
                'json' => $body,
            ]);
        } catch (ClientException $exception) {
            throw $exception;
        }

        return $response;
    }

    private function get(string $endpoint): ResponseInterface
    {
        try {
            $response = $this->client->get($this->createEndpoint($endpoint));
        } catch (ClientException $exception) {
            throw $exception;
        }

        return $response;
    }

    private function createEndpoint($endpoint)
    {
        return $endpoint . $this->queryParameters;
    }

    public function expand($expandables)
    {
        if (is_array($expandables)) {
            $this->queryParameters = '?expand=' . implode('&expand=', $expandables);
        }

        if (is_string($expandables)) {
            $this->queryParameters = "?expand=$expandables";
        }

        return $this;
    }

    public function include($includables)
    {
        if (is_array($includables)) {
            $this->queryParameters = '?include=' . implode('&include=', $includables);
        }

        if (is_string($includables)) {
            $this->queryParameters = "?include=$includables";
        }

        return $this;
    }

    public function leverKey(): string
    {
        return $this->leverKey;
    }

    public function client(): GuzzleClient
    {
        return $this->client;
    }

    public function opportunities(): Collection
    {
        $response = $this->get('opportunities')->getBody()->getContents();

        return new Collection(
            json_decode($response, true)['data']
        );
    }


}
