<?php
namespace SymfonyRbac\Tests\Controller;

use SymfonyRbac\Tests\Fixtures\app\AppKernel;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
class DefaultControllerTest extends TestCase {
  public function testIndex() {
    $kernel = new AppKernel();
    $client = new KernelBrowser($kernel);
    $crawler = $client->request('GET', '/rbac/default');
    $code = $client->getResponse()->getStatusCode();
    $this->assertEquals(200, $code);
  }
}