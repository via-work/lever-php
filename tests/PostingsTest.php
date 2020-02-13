<?php

namespace ViaWork\LeverPhp\Tests;

use GuzzleHttp\Psr7\Response;

class PostingsTest extends TestCase
{
    /** @test */
    public function uris_are_generated_correctly()
    {
        $this->mockHandler->append(
            new Response(200, [], '{"data": {}}'),
            new Response(200, [], '{"data": {}}'),
            new Response(200, [], '{"data": {}}'),
            new Response(200, [], '{"data": {}}'),
            new Response(200, [], '{"data": {}}'),
            );

        $this->lever->postings()->fetch();
        $this->lever->postings()->team('Accounting')->fetch();
        $this->lever->postings('6a1e4b79-75a3-454f-9417-ea79612b9585')->team('BizOps')->fetch();
        $this->lever->postings()->team(['Accounting', 'Product'])->fetch();
        $this->lever->postings('6a1e4b79-75a3-454f-9417-ea79612b9585')->sendConfirmationEmail()->apply(['ipAddress' => '184.23.195.146']);

        $this->assertEquals(
            'postings',
            (string)$this->container[0]['request']->getUri()
        );

        $this->assertEquals(
            'postings?team=Accounting',
            (string)$this->container[1]['request']->getUri()
        );

        $this->assertEquals(
            'postings/6a1e4b79-75a3-454f-9417-ea79612b9585?team=BizOps',
            (string)$this->container[2]['request']->getUri()
        );


        $this->assertEquals(
            'postings?team=Accounting&team=Product',
            (string)$this->container[3]['request']->getUri()
        );

        $this->assertEquals(
            'postings/6a1e4b79-75a3-454f-9417-ea79612b9585/apply?send_confirmation_email=true',
            (string)$this->container[4]['request']->getUri()
        );
    }
}
