<?php

namespace ViaWork\LeverPhp\Tests;

use GuzzleHttp\Psr7\Response;
use ReflectionClass;
use ViaWork\LeverPhp\LeverPhp;

class LeverPhpTest extends TestCase
{
    /** @test */
    public function options_method_gives_correct_output_when_has_files_included()
    {
        $class = new ReflectionClass(LeverPhp::class);
        $method = $class->getMethod('options');
        $method->setAccessible(true);

        $body = [
            'name' => 'Shane Smith',
            'headline' => 'Brickly LLC, Vandelay Industries, Inc, Central Perk',
            'emails' => ['shane@exampleq3.com', 'alfonso@via.work'],
            'resumeFile' => [
                'file' => 'file-content-example',
                'type' => 'application/pdf',
                'name' => 'resume1.pdf',
            ],
            'file' => [
                'file' => 'file-content-example',
                'type' => 'application/pdf',
                'name' => 'resume2.pdf',
            ],
            'files' => [
                'file' => 'file-content-example',
                'type' => 'application/pdf',
                'name' => 'resume3.pdf',
            ],
            'phones' => [
                [
                    'value' => '(123) 456-7891',
                    'type' => 'work',
                ],
            ],
            'location' => 'Oakland',
            'links' => [
                'indeed.com/r/Shane-Smith/0b7c87f6b246d2bc',
            ],
            'archived' => [
                'reason' => 'The reason',
                'archivedAt' => 146687167166,
            ],
        ];

        $expectedOutput = [
            'multipart' => [
                [
                    'name' => 'name',
                    'contents' => 'Shane Smith',
                ],
                [
                    'name' => 'headline',
                    'contents' => 'Brickly LLC, Vandelay Industries, Inc, Central Perk',
                ],
                [
                    'name' => 'emails[]',
                    'contents' => 'shane@exampleq3.com',
                ],
                [
                    'name' => 'emails[]',
                    'contents' => 'alfonso@via.work',
                ],
                [
                    'name' => 'resumeFile',
                    'contents' => 'file-content-example',
                    'filename' => 'resume1.pdf',
                    'headers' => [
                        'Content-Type' => 'application/pdf',
                    ],
                ],
                [
                    'name' => 'file',
                    'contents' => 'file-content-example',
                    'filename' => 'resume2.pdf',
                    'headers' => [
                        'Content-Type' => 'application/pdf',
                    ],
                ],
                [
                    'name' => 'files',
                    'contents' => 'file-content-example',
                    'filename' => 'resume3.pdf',
                    'headers' => [
                        'Content-Type' => 'application/pdf',
                    ],
                ],
                [
                    'name' => 'phones[]',
                    'contents' => [
                        'value' => '(123) 456-7891',
                        'type' => 'work',
                    ],
                ],
                [
                    'name' => 'location',
                    'contents' => 'Oakland',
                ],
                [
                    'name' => 'links[]',
                    'contents' => 'indeed.com/r/Shane-Smith/0b7c87f6b246d2bc',
                ],
                [
                    'name' => 'archived[reason]',
                    'contents' => 'The reason',
                ],
                [
                    'name' => 'archived[archivedAt]',
                    'contents' => 146687167166,
                ],
            ],
        ];

        $output = $method->invokeArgs($this->lever->hasFiles(), [$body]);

        $this->assertEquals($expectedOutput, $output);
    }

    /** @test */
    public function options_method_gives_correct_output_when_has_files_is_not_included()
    {
        $class = new ReflectionClass(LeverPhp::class);
        $method = $class->getMethod('options');
        $method->setAccessible(true);

        $body = [
            'name' => 'Shane Smith',
            'headline' => 'Brickly LLC, Vandelay Industries, Inc, Central Perk',
            'emails' => ['shane@exampleq3.com', 'alfonso@via.work'],
            'phones' => [
                [
                    'value' => '(123) 456-7891',
                    'type' => 'work',
                ],
            ],
            'location' => 'Oakland',
            'links' => [
                'indeed.com/r/Shane-Smith/0b7c87f6b246d2bc',
            ],
            'archived' => [
                'reason' => 'The reason',
                'archivedAt' => 146687167166,
            ],
        ];

        $output = $method->invokeArgs($this->lever, [$body]);

        $this->assertEquals(['json' => $body], $output);
    }

    /** @test */
    public function check_exponential_backoff_works()
    {
        $this->mockHandler->append(
            new Response(429, [], '{"data": {}}'),
            new Response(429, [], '{"data": {}}'),
            new Response(429, [], '{"data": {}}'),
            new Response(200, [], '{"data": {}}'),
            );

        $this->lever->opportunities()->fetch();

        foreach ($this->container as $item) {
            if (array_key_exists('delay', $item['options'])) {
                $this->assertEquals(pow(2, $item['options']['retries']) * self::BACKOFF_TEST, $item['options']['delay']);
            }
        }

        $this->assertCount(4, $this->container);
    }
}
