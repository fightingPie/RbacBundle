<?php

namespace SymfonyRbac\Tests\Controller;

use SymfonyRbac\Rbac\Rules\TestRule;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class AssignmentControllerTest extends WebTestCase
{

    protected $client;

    protected function setUp()
    {
        parent::setUp();
        $this->client = static::createClient();

    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', '/rbac/assignments/31');
        $code = $this->client->getResponse()->getContent();
    }

    public function testAdd(): void
    {
        $mock = [
            [
                'name' => 'kpi_admins',
            ]
        ];

        $jsonEn = new JsonEncoder();
        $json = $jsonEn->encode($mock, 'json');

        $crawler = $this->client->request('POST', '/rbac/assignments/user/31/add', [], [], [], $json);
        $res = $this->client->getResponse()->getContent();

    }

    public function testRemove(): void
    {

        $crawler = $this->client->request('PATCH', '/rbac/assignments/user/31/role/kpi_admins');
        $res = $this->client->getResponse()->getContent();

    }
}