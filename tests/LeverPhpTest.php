<?php

namespace ViaWork\LeverPhp\Tests;

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
            'headers' => [
                'Content-Type' => 'multipart/form-data',
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
}
