<?php

namespace ViaWork\LeverPhp;

use Exception;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\LazyCollection;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\ClientException;
use GrahamCampbell\GuzzleFactory\GuzzleFactory;

class LeverPhp
{
    private $leverKey = '';

    private $endpoint = '';

    private $client;

    private $options = ['query' => []];

    /**
     * LeverPhp constructor.
     * @param string|null $leverKey
     * @param GuzzleClient|null $client
     */
    public function __construct(string $leverKey = null, GuzzleClient $client = null)
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
                    'auth' => [$leverKey, ''],
                ]
            );
    }

    private function reset()
    {
        $this->endpoint = '';
        $this->options = ['query' => []];
    }

    private function post(array $body): ResponseInterface
    {
        $this->options['query'] = build_query($this->options['query']);
        try {
            $response = $this->client->post($this->endpoint,
                array_merge(array('json' => $body), $this->options));
        } catch (ClientException $exception) {
            throw $exception;
        } finally {
            $this->reset();
        }

        return $response;
    }

    private function get(): ResponseInterface
    {
//        $this->options['query'] = build_query($this->options['query']);

        try {
            $response = $this->client->get($this->endpoint, $this->options);
        } catch (ClientException $exception) {
            throw $exception;
        } finally {
            $this->reset();
        }

        return $response;
    }

    public function create(array $body): array
    {
        $response = $this->responseToArray($this->post($body));

        return $response['data'];
    }

    public function update(array $body): array
    {
        return $this->create($body);
    }

    /** @return LazyCollection|array */
    public function fetch()
    {
        $response = $this->responseToArray($this->get());

        if (!array_key_exists('hasNext', $response)) {
            return $response['data'];
        }

        return LazyCollection::make(function () use ($response) {
            do {
                foreach ($response['data'] as $item) {
                    yield $item;
                }

                $response['data'] = [];

                if (!empty($response['next'])) {
                    $response = $this->responseToArray(
                        $this->addParameter('offset', $response['next'])->get()
                    );
                }

            } while (count($response['data']) > 0);
        });
    }

    public function leverKey(): string
    {
        return $this->leverKey;
    }

    public function client(): GuzzleClient
    {
        return $this->client;
    }

    private function responseToArray(ResponseInterface $response): array
    {
        return json_decode($response->getBody()->getContents(), true);
    }

    public function expand(string $expandable)
    {
        return $this->addParameter('expand', $expandable);
    }

    public function performAs(string $userId)
    {
        return $this->addParameter('perform_as', $userId);
    }

    public function include(string $includable)
    {
        return $this->addParameter('include', $includable);
    }

    /**
     * @param string $field
     * @param string|array $value
     * @return $this
     */
    public function addParameter(string $field, $value)
    {
        if (!empty($field) && !empty($value)) {
            $this->options['query'][$field] = is_array($value)
                ? implode(',', $value)
                : $value;
        }

        return $this;
    }

    public function opportunities(string $opportunityId = '')
    {
        $this->endpoint = 'opportunities' . (empty($opportunityId) ? '' : '/' . $opportunityId);

        return $this;
    }

    public function offers()
    {
        // TODO next release.
        // $regex = '/^opportunities\/[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$/';
        // if (preg_match($regex, $this->endpoint) === 0){
        //     throw new Exception('Did not chain methods in correct order.');
        // }

        $this->endpoint .= '/offers';

        return $this;
    }

    public function postings(string $postingId = '')
    {
        $this->endpoint = 'postings' . (empty($postingId) ? '' : '/' . $postingId);

        return $this;
    }

    public function state(string $state)
    {
        if (!in_array($state, ['published', 'internal', 'closed', 'draft', 'pending', 'rejected'])) {
            throw new Exception('Not a valid state');
        }

        return $this->addParameter('state', $state);
    }


    public function team(string $team)
    {
        return $this->addParameter('state', $team);

    }
}
