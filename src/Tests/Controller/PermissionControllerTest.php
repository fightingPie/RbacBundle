<?php

namespace SymfonyRbac\Tests\Controller;

use SymfonyRbac\Rbac\Rules\TestRule;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class PermissionControllerTest extends WebTestCase
{

    protected $client;

    protected function setUp()
    {
        parent::setUp();
        $this->client = static::createClient();

    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', '/rbac/permissions');
        $code = $this->client->getResponse()->getContent();
    }

    public function testAdd(): void
    {
        $mock = [
            'name' => 'kpi_admin',
            'description' => 'KPI管理员',
            'ruleName' => TestRule::className(),
            'data' => null,
        ];

        $jsonEn = new JsonEncoder();
        $json = $jsonEn->encode($mock, 'json');

        $crawler = $this->client->request('POST', '/rbac/permissions/add', [], [], [], $json);
        $res = $this->client->getResponse()->getContent();

    }

    public function testEdit(): void
    {
        $mock = [
            'name' => 'kpi_admins',
            'description' => 'KPI管理员',
            'ruleName' => TestRule::className(),
            'data' => null,
        ];

        $jsonEn = new JsonEncoder();
        $json = $jsonEn->encode($mock, 'json');

        $crawler = $this->client->request('PATCH', '/rbac/permissions/kpi_admin', [], [], [], $json);
        $res = $this->client->getResponse()->getContent();

    }
}