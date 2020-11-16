<?php


namespace SymfonyRbac\Tests\Entity;

use SymfonyRbac\Entity\AuthItem;
use SymfonyRbac\Tests\TestCase;

class AuthItemRepositoryTest extends TestCase
{
  /**
   * @var
   */
  private $entityManager;

  public function testSearchByName()
  {
    $has = $this->container->has('doctrine');
    $this->entityManager = $this->container->get('doctrine')
      ->getManager();
    $product = $this->entityManager
      ->getRepository(AuthItem::class)
      ->findOneBy(['name' => '1'])
    ;
    $children = $product->getChild()->toArray();
    $origins = $product->getOrigin()->toArray();
    foreach ($origins  as $item) {

      $name = $item->getName();

    }

  }

  protected function tearDown(): void
  {
    parent::tearDown();

    // doing this is recommended to avoid memory leaks
    $this->entityManager->close();
    $this->entityManager = null;
  }
}