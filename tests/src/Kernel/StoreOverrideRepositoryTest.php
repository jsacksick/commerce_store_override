<?php

namespace Drupal\Tests\commerce_store_override\Kernel;

use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_store_override\StoreOverride;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * @coversDefaultClass \Drupal\commerce_store_override\StoreOverrideRepository
 * @group commerce
 */
class StoreOverrideRepositoryTest extends CommerceKernelTestBase {

  /**
   * The store override repository.
   *
   * @var \Drupal\commerce_store_override\StoreOverrideRepositoryInterface
   */
  protected $repository;

  /**
   * Test products.
   *
   * @var \Drupal\commerce_product\Entity\ProductInterface[]
   */
  protected $products = [];

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_product',
    'commerce_store_override',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product');
    $this->installConfig(['commerce_product']);
    $this->installSchema('commerce_store_override', ['commerce_store_override']);

    $this->repository = $this->container->get('commerce_store_override.repository');

    $first_product = Product::create([
      'type' => 'default',
      'title' => 'Test1',
    ]);
    $first_product->save();
    $second_product = Product::create([
      'type' => 'default',
      'title' => 'Test2',
    ]);
    $second_product->save();
    $this->products = [$first_product, $second_product];
  }

  /**
   * @covers ::save
   * @covers ::load
   * @covers ::loadMultipleByEntity
   * @covers ::delete
   * @covers ::deleteByStore
   * @covers ::deleteByEntity
   */
  public function testRepository() {
    $second_store = $this->createStore('Second store', 'admin@example.com');
    $first_definition = [
      'data' => [
        'title' => ['value' => 'This is a custom title'],
      ],
      'status' => TRUE,
      'created' => 1573344000,
    ];
    $second_definition = [
      'data' => [
        'title' => ['value' => 'This is another custom title'],
      ],
      'status' => FALSE,
      'created' => 1573344000,
    ];

    $store_overrides = [];
    $store_overrides[0] = StoreOverride::create($this->store, $this->products[0], $first_definition);
    $store_overrides[1] = StoreOverride::create($second_store, $this->products[0], $second_definition);
    $store_overrides[2] = StoreOverride::create($this->store, $this->products[1], $first_definition);
    $store_overrides[3] = StoreOverride::create($second_store, $this->products[1], $second_definition);
    foreach ($store_overrides as $store_override) {
      $this->repository->save($store_override);
    }

    $loaded_store_override = $this->repository->load($this->store, $this->products[0]);
    $this->assertNotNull($loaded_store_override);
    $this->assertEquals($store_overrides[0]->toArray(), $loaded_store_override->toArray());

    $loaded_store_override = $this->repository->load($second_store, $this->products[0]);
    $this->assertNotNull($loaded_store_override);
    $this->assertEquals($store_overrides[1]->toArray(), $loaded_store_override->toArray());

    $loaded_store_override = $this->repository->load($this->store, $this->products[1]);
    $this->assertNotNull($loaded_store_override);
    $this->assertEquals($store_overrides[2]->toArray(), $loaded_store_override->toArray());

    $loaded_store_override = $this->repository->load($second_store, $this->products[1]);
    $this->assertNotNull($loaded_store_override);
    $this->assertEquals($store_overrides[3]->toArray(), $loaded_store_override->toArray());

    $loaded_store_overrides = $this->repository->loadMultipleByEntity($this->products[0]);
    $this->assertEquals([$store_overrides[0], $store_overrides[1]], $loaded_store_overrides);

    $loaded_store_overrides = $this->repository->loadMultipleByEntity($this->products[1]);
    $this->assertEquals([$store_overrides[2], $store_overrides[3]], $loaded_store_overrides);

    $this->repository->deleteByStore($second_store);
    $loaded_store_overrides = $this->repository->loadMultipleByEntity($this->products[0]);
    $this->assertEquals([$store_overrides[0]], $loaded_store_overrides);
    $loaded_store_overrides = $this->repository->loadMultipleByEntity($this->products[1]);
    $this->assertEquals([$store_overrides[2]], $loaded_store_overrides);

    $this->repository->deleteByEntity($this->products[0]);
    $loaded_store_overrides = $this->repository->loadMultipleByEntity($this->products[0]);
    $this->assertEmpty($loaded_store_overrides);
    $loaded_store_overrides = $this->repository->loadMultipleByEntity($this->products[1]);
    $this->assertEquals([$store_overrides[2]], $loaded_store_overrides);

    $this->repository->delete($store_overrides[2]);
    $loaded_store_override = $this->repository->load($this->store, $this->products[1]);
    $this->assertNull($loaded_store_override);
  }

}
