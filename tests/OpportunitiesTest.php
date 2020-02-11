<?php

namespace ViaWork\LeverPhp\Tests;

use GuzzleHttp\Psr7\Response;
use Illuminate\Support\LazyCollection;

class OpportunitiesTest extends TestCase
{
    private string $offers = __DIR__ . '/fixtures/offers.json';
    private string $opportunity = __DIR__ . '/fixtures/opportunity.json';
    private string $opportunities = __DIR__ . '/fixtures/opportunities.json';
    private string $opportunitiesNextFalse = __DIR__ . '/fixtures/opportunities-hasNext-false.json';

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
            (string)$this->container[0]['request']->getUri()
        );
    }

    /** @test */
    public function include_and_expand_work_correctly()
    {
        for ($i = 0; $i < 4; $i++) {
            $this->mockHandler->append(new Response(200, [], '{"data": {}}'));
        }

        $this->lever->opportunities()->include('followers')->fetch();
        $this->lever->include('followers')->opportunities()->fetch();
        $this->lever->opportunities()->expand('applications')->fetch();
        $this->lever->include('followers')->opportunities()->expand('applications')->fetch();

        $this->assertEquals(
            'opportunities?include=followers',
            (string)$this->container[0]['request']->getUri()
        );

        $this->assertEquals(
            'opportunities?include=followers',
            (string)$this->container[1]['request']->getUri()
        );

        $this->assertEquals(
            'opportunities?expand=applications',
            (string)$this->container[2]['request']->getUri()
        );

        $this->assertEquals(
            'opportunities?include=followers&expand=applications',
            (string)$this->container[3]['request']->getUri()
        );
    }

    /** @test */
    public function fetching_offers()
    {
        $this->mockHandler->append(new Response(200, [], file_get_contents($this->offers)),);

        $offers = $this->lever->opportunities('250d8f03-738a-4bba-a671-8a3d73477145')->offers()->fetch();

        $this->assertInstanceOf(LazyCollection::class, $offers);

        $this->assertCount(2, $offers);

        $this->assertEquals(
            'opportunities/250d8f03-738a-4bba-a671-8a3d73477145/offers',
            (string)$this->container[0]['request']->getUri()
        );

    }
}
