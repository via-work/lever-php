<?php

namespace ViaWork\LeverPhp\Tests;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\LazyCollection;

class OpportunitiesTest extends TestCase
{
    private $offers = __DIR__.'/fixtures/offers.json';
    private $opportunity = __DIR__.'/fixtures/opportunity.json';
    private $opportunities = __DIR__.'/fixtures/opportunities.json';
    private $opportunitiesNextFalse = __DIR__.'/fixtures/opportunities-hasNext-false.json';

    /** @test */
    public function automated_pagination_works_correctly_on_opportunities()
    {
        $this->mockHandler->append(
            new Response(200, [], file_get_contents($this->opportunities)),
            new Response(200, [], file_get_contents($this->opportunitiesNextFalse)),
            );

        $opportunities = $this->lever->opportunities()->fetch();

        $this->assertInstanceOf(LazyCollection::class, $opportunities);

        $this->assertCount(6, $opportunities);
    }

    /** @test */
    public function retrieve_a_single_opportunity()
    {
        $this->mockHandler->append(
            new Response(200, [], file_get_contents($this->opportunity)),
            );

        $opportunity = $this->lever->opportunities('250d8f03-738a-4bba-a671-8a3d73477145')->fetch();

        $this->assertIsArray($opportunity);

        $this->assertEquals(
            'opportunities/250d8f03-738a-4bba-a671-8a3d73477145',
            (string) $this->container[0]['request']->getUri()
        );
    }

    /** @test */
    public function add_parameters_work_correctly()
    {
        for ($i = 0; $i < 4; $i++) {
            $this->mockHandler->append(new Response(200, [], '{"data": {}}'));
        }

        $this->lever->opportunities()->include('followers')->include('content')->fetch();
        $this->lever->include(['followers', 'content'])->opportunities()->fetch();
        $this->lever->opportunities()->expand('applications')->expand(['user', 'posting'])->fetch();
        $this->lever->include('followers')->opportunities()->expand('applications')->fetch();

        $this->assertEquals(
            'opportunities?include=followers&include=content',
            (string) $this->container[0]['request']->getUri()
        );

        $this->assertEquals(
            'opportunities?include=followers&include=content',
            (string) $this->container[1]['request']->getUri()
        );

        $this->assertEquals(
            'opportunities?expand=applications&expand=user&expand=posting',
            (string) $this->container[2]['request']->getUri()
        );

        $this->assertEquals(
            'opportunities?include=followers&expand=applications',
            (string) $this->container[3]['request']->getUri()
        );
    }

    /** @test */
    public function create_opportunity()
    {
        $this->mockHandler->append(new Response(200, [], file_get_contents($this->opportunity)), );

        $newOpportunity = [
            'name' => 'Shane Smith',
            'headline' => 'Brickly LLC, Vandelay Industries, Inc, Central Perk',
            'stage' => '00922a60-7c15-422b-b086-f62000824fd7',
            'email',
        ];

        $opportunity = $this->lever->opportunities()
            ->performAs('8d49b010-cc6a-4f40-ace5-e86061c677ed')
            ->create($newOpportunity);

        $this->assertIsArray($opportunity);

        $this->assertEquals(
            'opportunities?perform_as=8d49b010-cc6a-4f40-ace5-e86061c677ed',
            (string) $this->container[0]['request']->getUri()
        );
    }

    /** @test */
    public function fail_to_create_opportunity_when_no_perform_as_parameter_included()
    {
        $this->expectException(ClientException::class);

        $this->mockHandler->append(new Response(400, [],
            '{"code": "BadRequestError", "message": "Missing perform_as parameter. Please specify a user for which to perform this create."}'
        ));

        $newOpportunity = [
            'name' => 'Shane Smith',
            'headline' => 'Brickly LLC, Vandelay Industries, Inc, Central Perk',
            'stage' => '00922a60-7c15-422b-b086-f62000824fd7',
            'email',
        ];

        $opportunity = $this->lever->opportunities()->create($newOpportunity);

        $this->assertEquals(
            'opportunities',
            (string) $this->container[0]['request']->getUri()
        );
    }

    /** @test */
    public function fetching_offers()
    {
        $this->mockHandler->append(new Response(200, [], file_get_contents($this->offers)), );

        $offers = $this->lever->opportunities('250d8f03-738a-4bba-a671-8a3d73477145')->offers()->fetch();

        $this->assertInstanceOf(LazyCollection::class, $offers);

        $this->assertCount(2, $offers);

        $this->assertEquals(
            'opportunities/250d8f03-738a-4bba-a671-8a3d73477145/offers',
            (string) $this->container[0]['request']->getUri()
        );
    }

    /** @test */
    public function uris_are_generated_correctly()
    {
        $this->mockHandler->append(
            new Response(200, [], '{"data": {}}'),
            new Response(200, [], '{"data": {}}'),
            new Response(200, [], '{"data": {}}'),
            );

        $this->lever->opportunities('250d8f03-738a-4bba-a671-8a3d73477145')->resumes()->fetch();
        $this->lever->opportunities('250d8f03-738a-4bba-a671-8a3d73477145')
            ->resumes('6a1e4b79-75a3-454f-9417-ea79612b9585')->fetch();
        $this->lever->opportunities('250d8f03-738a-4bba-a671-8a3d73477145')
            ->resumes('6a1e4b79-75a3-454f-9417-ea79612b9585')->download()->fetch();

        $this->assertEquals(
            'opportunities/250d8f03-738a-4bba-a671-8a3d73477145/resumes',
            (string) $this->container[0]['request']->getUri()
        );

        $this->assertEquals(
            'opportunities/250d8f03-738a-4bba-a671-8a3d73477145/resumes/6a1e4b79-75a3-454f-9417-ea79612b9585',
            (string) $this->container[1]['request']->getUri()
        );

        $this->assertEquals(
            'opportunities/250d8f03-738a-4bba-a671-8a3d73477145/resumes/6a1e4b79-75a3-454f-9417-ea79612b9585/download',
            (string) $this->container[2]['request']->getUri()
        );
    }
}
