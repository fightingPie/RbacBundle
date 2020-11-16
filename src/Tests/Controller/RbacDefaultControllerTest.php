<?php

namespace SymfonyRbac\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RbacDefaultControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/rbac/routes');
        $code = $client->getResponse()->getContent();
        $this->assertEquals(200, $code);
    }

    public function testRoleMapping()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/rbac/roles/mapping');
        $code = $client->getResponse()->getContent();
        $this->assertEquals(200, $code);
    }


    public function testPermissionMapping() {
        $client = static::createClient();
        $crawler = $client->request('GET', '/rbac/permissions/mapping');
        $code = $client->getResponse()->getContent();
        $this->assertEquals(200, $code);
    }
}