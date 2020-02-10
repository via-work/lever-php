<?php

namespace ViaWork\LeverPhp;

use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\LazyCollection;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\ClientException;
use GrahamCampbell\GuzzleFactory\GuzzleFactory;

class LeverPhp
{
    /** @var string */
    private $leverKey;

    /** @var GuzzleClient */
    private $client;

    /** @var array */
    private $queryParameters = ['query' => []];

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
            $response = $this->client->get($endpoint, $this->queryParameters);
        } catch (ClientException $exception) {
            throw $exception;
        }

        return $response;
    }


    public function expand(string $expandable)
    {
        return $this->addQueryString('expand', $expandable);
    }

    public function include(string $includable)
    {
        return $this->addQueryString('include', $includable);
    }

    public function addQueryString(string $field, string $value)
    {
        if (!empty($field) && !empty($value)) {
            $this->queryParameters['query'][$field] = $value;
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

    public function opportunities(): LazyCollection
    {
        return LazyCollection::make(function () {

            $nextToken = '';

            do {
                $response = $this->responseToArray(
                    $this->addQueryString('offset', $nextToken)->get('opportunities')
                );

                foreach ($response['data'] as $item) {
                    yield $item;
                }

                $nextToken = $response['next'] ?? '';

            } while ($response['hasNext']);

        });
    }

    private function responseToArray(ResponseInterface $response)
    {
        return json_decode($response->getBody()->getContents(), true);
    }


}
