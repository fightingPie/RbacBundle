<?php

namespace SymfonyRbac\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;

class RoleControllerTest extends WebTestCase
{

    protected $client;

    protected function setUp()
    {
        parent::setUp();
        $this->client = static::createClient();

    }

    public function testUid()
    {
        $ulid = new Ulid();  // e.g. 01AN4Z07BY79KA1307SR9X4MV3

        $ulidValue = '01E439TP9XJZ9RPFH3T1PYBCR8';
        $ulid = Ulid::fromString($ulidValue);

        $uuid = Uuid::v1(); // $uuid is an instance of Symfony\Component\Uid\UuidV1

// UUID type 4 generates a random UUID, so you don't have to pass any constructor argument.
        $uuid = Uuid::v4(); // $uuid is an instance of Symfony\Component\Uid\UuidV4

// UUID type 3 and 5 generate a UUID hashing the given namespace and name. Type 3 uses
// MD5 hashes and Type 5 uses SHA-1. The namespace is another UUID (e.g. a Type 4 UUID)
// and the name is an arbitrary string (e.g. a product name; if it's unique).
        $namespace = Uuid::v4();
        $name = 'admin';

        $uuid = Uuid::v3($namespace, $name); // $uuid is an instance of Symfony\Component\Uid\UuidV3
        $uuid = Uuid::v5($namespace, $name); // $uuid is an instance of Symfony\Component\Uid\UuidV5

// UUID type 6 is not part of the UUID standard. It's lexicographically sortable
// (like ULIDs) and contains a 60-bit timestamp and 63 extra unique bits.
// It's defined in http://gh.peabody.io/uuidv6/
        $uuid = Uuid::v6(); // $uuid is an instance of Symfony\Component\Uid\UuidV6
    }

    public function testIndex()
    {
//        $crawler = $this->client->request('GET', '/rbac/roles', ['name' => 'admin', 'status' => 1, 'user' => 31, 'permission' => 'admin_departments_add']);
        $crawler = $this->client->request('GET', '/rbac/roles');
        $code = $this->client->getResponse()->getContent();
    }

    public function testAdd(): void
    {
        $mock = [
            'alias' => '用户管理员',
            'description' => '用户管理员',
//            'ruleName' => TestRule::className(),
//            'data' => null,
            'child' => [
                [
                    'name' => 'admin_departments_add'
                ]
            ]
        ];

        $jsonEn = new JsonEncoder();
        $json = $jsonEn->encode($mock, 'json');

        $crawler = $this->client->request('POST', '/rbac/roles', [], [], [], $json);
        $res = $this->client->getResponse()->getContent();

    }

    public function testView(): void
    {
        $crawler = $this->client->request('GET', '/rbac/roles/admin');
        $res = $this->client->getResponse()->getContent();

    }

    public function testEdit(): void
    {
        $mock = [
            'alias' => 'kpi_admin',
            'description' => 'KPI管理员s',
//            'ruleName' => TestRule::className(),
//            'data' => null,
            'child' => [
                [
                    'name' => 'admin_departments_add'
                ],
                [
                    'name' => 'admin_departments_export'
                ],
            ]
        ];

        $jsonEn = new JsonEncoder();
        $json = $jsonEn->encode($mock, 'json');

        $crawler = $this->client->request('PATCH', '/rbac/roles/01EPZNY93RW117PPVRPKHGVXMF', [], [], [], $json);
        $res = $this->client->getResponse()->getContent();

    }

    public function testSwitch(): void
    {
        $mock = [
            'statuss' => 0
        ];

        $jsonEn = new JsonEncoder();
        $json = $jsonEn->encode($mock, 'json');

        $crawler = $this->client->request('PATCH', '/rbac/roles/admin/switch', [], [], [], $json);
        $res = $this->client->getResponse()->getContent();

    }


    public function testPermission(): void
    {
        $mock = [
            [
                'name' => 'Keyboard3'
            ]
        ];

        $jsonEn = new JsonEncoder();
        $json = $jsonEn->encode($mock, 'json');

        $crawler = $this->client->request('POST', '/rbac/roles/kpi_admins/permissions', [], [], [], $json);
        $res = $this->client->getResponse()->getContent();

    }

    public function testAssign(): void
    {
        $mock = [
            [
                'id' => 31
            ],
            [
                'id' => 2189
            ],
        ];

        $jsonEn = new JsonEncoder();
        $json = $jsonEn->encode($mock, 'json');

        $crawler = $this->client->request('POST', '/rbac/roles/01EPZRE7Q31TBQAMK152VNA4AZ/assign', [], [], [], $json);
        $res = $this->client->getResponse()->getContent();

    }
}