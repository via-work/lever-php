<?php

namespace ViaWork\LeverPhp;

use Exception;
use GrahamCampbell\GuzzleFactory\GuzzleFactory;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\HandlerStack;
use Illuminate\Support\LazyCollection;
use Psr\Http\Message\ResponseInterface;
use Spatie\GuzzleRateLimiterMiddleware\RateLimiterMiddleware;
use Spatie\GuzzleRateLimiterMiddleware\Store;

class LeverPhp
{
    const BACKOFF_TIME = 1500;

    private $leverKey = '';

    private $endpoint = '';

    private $client;

    private $options = [];

    /**
     * LeverPhp constructor.
     * @param string|null $leverKey
     * @param GuzzleClient|null $client
     * @param Store|null $store
     */
    public function __construct(string $leverKey = null, GuzzleClient $client = null, Store $store = null)
    {
        $this->leverKey = $leverKey;

        $stack = HandlerStack::create();

        $stack->push(DuplicateAggregatorMiddleware::buildQuery());

        $stack->push(RateLimiterMiddleware::perSecond(10, $store));

        $this->client = $client ?? GuzzleFactory::make(
                [
                    'base_uri' => 'https://api.lever.co/v1/',
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                    'auth' => [$leverKey, ''],
                    'handler' => $stack,
                ],
                self::BACKOFF_TIME
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
        if (isset($this->options['hasFiles'])) {
            $options = [];

            foreach ($body as $key => $item) {

                // TODO add support for files[] and automate filename and headers fields.
                if (in_array($key, ['file', 'files', 'resumeFile'])) {
                    $options[] = [
                        'name' => $key,
                        'contents' => $item['file'],
                        'filename' =>  $item['name'],
                        'headers' => ['Content-Type' => $item['type']],
                    ];

                    continue;
                }

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

            unset($this->options['hasFiles']);

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

    public function stages()
    {
        // TODO next release.
        // $regex = '/^opportunities\/[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$/';
        // if (preg_match($regex, $this->endpoint) === 0){
        //     throw new Exception('Did not chain methods in correct order.');
        // }

        $this->endpoint .= '/stage';

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

    /**
     * @param array|string $department
     * @return $this
     */
    public function department($department)
    {
        return $this->addParameter('department', $department);
    }

    /**
     * @return $this
     */
    public function parse()
    {
        return $this->addParameter('parse', 'true');
    }

    public function hasFiles()
    {
        $this->options['hasFiles'] = true;

        return $this;
    }

    /**
     * @param array|string $email
     * @return $this
     */
    public function email($email)
    {
        return $this->addParameter('email', $email);
    }

    /**
     * @param array|string $stageId
     * @return $this
     */
    public function stage($stageId)
    {
        return $this->addParameter('stage_id', $stageId);
    }

    /**
     * @param array|string $postingId
     * @return $this
     */
    public function posting($postingId)
    {
        return $this->addParameter('posting_id', $postingId);
    }
}
