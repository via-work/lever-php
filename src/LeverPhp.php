<?php

namespace ViaWork\LeverPhp;

use Exception;
use GrahamCampbell\GuzzleFactory\GuzzleFactory;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\HandlerStack;
use Illuminate\Support\LazyCollection;
use Psr\Http\Message\ResponseInterface;

class LeverPhp
{
    private $leverKey = '';

    private $endpoint = '';

    private $client;

    private $options = [];

    /**
     * LeverPhp constructor.
     * @param string|null $leverKey
     * @param GuzzleClient|null $client
     */
    public function __construct(string $leverKey = null, GuzzleClient $client = null)
    {
        $this->leverKey = $leverKey;

        $stack = HandlerStack::create();

        $stack->push(DuplicateAggregatorMiddleware::buildQuery());

        // TODO pass RateLimiterMiddleware, check if compatible with exponential backoff
        $this->client = $client ?? GuzzleFactory::make(
                [
                    'base_uri' => 'https://api.lever.co/v1/',
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                    'auth' => [$leverKey, ''],
                    'handler' => $stack,
                ]
            );
    }

    private function reset()
    {
        $this->endpoint = '';
        $this->options = [];
    }

    private function post(array $body): ResponseInterface
    {
        try {
            $response = $this->client->post($this->endpoint, $this->options($body));
        } catch (ClientException $exception) {
            throw $exception;
        } finally {
            $this->reset();
        }

        return $response;
    }

    private function options($body)
    {
        if (isset($this->options['headers']['Content-Type']) && $this->options['headers']['Content-Type'] === 'multipart/form-data') {
            $options = [];

            foreach ($body as $key => $item) {
                if (is_array($item)) {
                    foreach ($item as $subKey => $subItem) {
                        if (is_numeric($subKey)) {
                            $options[] = ['name' => $key.'[]', 'contents' => $subItem];
                        }

                        if (is_string($subKey)) {
                            $options[] = ['name' => "{$key}[{$subKey}]", 'contents' => $subItem];
                        }
                    }
                }

                if (is_string($item)) {
                    $options[] = ['name' => $key, 'contents' => $item];
                }
            }

            return array_merge(['multipart' => $options], $this->options);
        }

        return array_merge(['json' => $body], $this->options);
    }

    private function get(): ResponseInterface
    {
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

        if (! array_key_exists('hasNext', $response)) {
            return $response['data'];
        }

        return LazyCollection::make(function () use ($response) {
            do {
                foreach ($response['data'] as $item) {
                    yield $item;
                }

                $response['data'] = [];

                if (! empty($response['next'])) {
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

    public function expand($expandable)
    {
        return $this->addParameter('expand', $expandable);
    }

    public function performAs(string $userId)
    {
        return $this->addParameter('perform_as', $userId);
    }

    public function include($includable)
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
        if (! empty($field) && ! empty($value)) {
            $value = is_string($value) ? [$value] : $value;

            $this->options['query'][$field] = array_merge($this->options['query'][$field] ?? [], $value);
        }

        return $this;
    }

    public function opportunities(string $opportunityId = '')
    {
        $this->endpoint = 'opportunities'.(empty($opportunityId) ? '' : '/'.$opportunityId);

        return $this;
    }

    public function resumes(string $resumeId = '')
    {
        $this->endpoint .= '/resumes'.(empty($resumeId) ? '' : '/'.$resumeId);

        return $this;
    }

    public function download()
    {
        $this->endpoint .= '/download';

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
        $this->endpoint = 'postings'.(empty($postingId) ? '' : '/'.$postingId);

        return $this;
    }

    public function sendConfirmationEmail()
    {
        return $this->addParameter('send_confirmation_email', 'true');
    }

    public function apply(array $body): array
    {
        $this->endpoint .= '/apply';

        return $this->create($body);
    }

    public function state(string $state)
    {
        if (! in_array($state, ['published', 'internal', 'closed', 'draft', 'pending', 'rejected'])) {
            throw new Exception('Not a valid state');
        }

        return $this->addParameter('state', $state);
    }

    /**
     * @param array|string $team
     * @return $this
     */
    public function team($team)
    {
        return $this->addParameter('team', $team);
    }

    public function hasFiles()
    {
        $this->options['headers'] = ['Content-Type' => 'multipart/form-data'];

        return $this;
    }
}
