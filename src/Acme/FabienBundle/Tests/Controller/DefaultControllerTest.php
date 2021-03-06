<?php

namespace Acme\FabienBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class DefaultControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $client->request('GET', '/');

        $this->assertEquals(
            Response::HTTP_FOUND,
            $client->getResponse()->getStatusCode()
        );
    }
}
