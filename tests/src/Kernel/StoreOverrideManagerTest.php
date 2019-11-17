<?php

namespace Drupal\Tests\commerce_store_override\Kernel;

use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_store_override\StoreOverride;
use Drupal\commerce_store_override\StoreOverrideManager;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * @coversDefaultClass \Drupal\commerce_store_override\StoreOverrideManager
 * @group commerce
 */
class StoreOverrideManagerTest extends CommerceKernelTestBase {

  /**
   * The store override repository.
   *
   * @var \Drupal\commerce_store_override\StoreOverrideRepositoryInterface
   */
  protected $repository;

  /**
   * A test product.
   *
   * @var \Drupal\commerce_product\Entity\ProductInterface
   */
  protected $product;

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

    $product = Product::create([
      'type' => 'default',
      'title' => 'Test',
    ]);
    $product->save();
    $this->product = $product;
  }

  /**
   * @covers ::shouldOverride
   */
  public function testShouldOverride() {
    $current_store = $this->container->get('commerce_store.current_store');
    $entity_field_manager = $this->container->get('entity_field.manager');
    $route_provider = $this->container->get('router.route_provider');
    $routes = [
      'entity.commerce_product.edit_form' => FALSE,
      'entity.commerce_product.canonical' => TRUE,
    ];
    foreach ($routes as $route_name => $result) {
      $route = $route_provider->getRouteByName($route_name);
      $route_match = new RouteMatch($route_name, $route);
      $manager = new StoreOverrideManager($current_store, $route_match, $this->repository, $entity_field_manager, $this->entityTypeManager);

      // Confirm that an unsupported entity type can never be overridden.
      $this->assertFalse($manager->shouldOverride($this->store));
      // Confirm that a supported entity type can be overridden depending
      // on the current route.
      $this->assertEquals($result, $manager->shouldOverride($this->product));
    }
  }

  /**
   * @covers ::override
   */
  public function testOverride() {
    $definition = [
      'data' => [
        'title' => [
          'value' => 'Overridden test1',
        ],
      ],
      'status' => TRUE,
    ];
    $store_override = StoreOverride::create($this->store, $this->product, $definition);
    $this->repository->save($store_override);

    $second_store = $this->createStore('Second store', 'admin@example.com', 'online', FALSE);
    $definition = [
      'data' => [
        'title' => [
          'value' => 'Overridden test2',
        ],
      ],
      'status' => TRUE,
    ];
    $store_override = StoreOverride::create($second_store, $this->product, $definition);
    $this->repository->save($store_override);

    $manager = $this->container->get('commerce_store_override.manager');
    $manager->override($this->product);
    // Confirm that the default store's override was applied.
    $this->assertEquals('Overridden test1', $this->product->label());

    /** @var \Drupal\commerce_store\StoreStorage $store_storage */
    $store_storage = \Drupal::service('entity_type.manager')->getStorage('commerce_store');
    $store_storage->markAsDefault($second_store);
    $this->container->set('commerce_store.current_store', NULL);
    $this->container->set('commerce_store_override.manager', NULL);

    $manager = $this->container->get('commerce_store_override.manager');
    $manager->override($this->product);
    // Confirm that the new default store's override was applied.
    $this->assertEquals('Overridden test2', $this->product->label());

    // Confirm that the override is no longer applied if it is inactive.
    $this->product = $this->reloadEntity($this->product);
    $definition = [
      'data' => [
        'title' => [
          'value' => 'Overridden test2',
        ],
      ],
      'status' => FALSE,
    ];
    $store_override = StoreOverride::create($second_store, $this->product, $definition);
    $this->repository->save($store_override);
    $manager->override($this->product);
    $this->assertEquals('Test', $this->product->label());
  }

  /**
   * @covers::getAllowedFieldsOverride
   * @covers::getEnabledFieldsOverride
   */
  public function testAllowedAndEnabledFields() {
    $product_type = $this->entityTypeManager->getStorage('commerce_product_type')->load('default');
    /** @var \Drupal\commerce_store_override\StoreOverrideManagerInterface $manager */
    $manager = $this->container->get('commerce_store_override.manager');
    $allowed_fields = $manager->getAllowedFieldsOverride($product_type);
    $this->assertArrayHasKey('title', $allowed_fields);
    $this->assertArrayHasKey('body', $allowed_fields);

    $enabled_fields = $manager->getEnabledFieldsOverride($product_type);
    $this->assertEmpty($enabled_fields);
    $product_type->setThirdPartySetting('commerce_store_override', 'fields', ['title', 'body']);
    $product_type->save();
    $enabled_fields = $manager->getEnabledFieldsOverride($product_type);
    $this->assertTrue(in_array('title', $enabled_fields, TRUE));
    $this->assertTrue(in_array('body', $enabled_fields, TRUE));
  }

}
