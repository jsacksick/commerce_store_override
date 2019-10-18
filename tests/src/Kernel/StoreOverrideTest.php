<?php

namespace Drupal\Tests\commerce_store_override\Kernel;

use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_store_override\StoreOverride;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * @coversDefaultClass \Drupal\commerce_store_override\StoreOverride
 * @group commerce
 */
class StoreOverrideTest extends CommerceKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_product',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product');
    $this->installConfig(['commerce_product']);
  }

  /**
   * Tests the constructor and definition checks.
   *
   * @covers ::__construct
   *
   * @dataProvider invalidDefinitionProvider
   */
  public function testInvalidConstruct($definition, $message) {
    $this->setExpectedException(\InvalidArgumentException::class, $message);
    new StoreOverride($definition);
  }

  /**
   * Invalid constructor definitions.
   *
   * @return array
   *   The definitions.
   */
  public function invalidDefinitionProvider() {
    return [
      [
        [], 'Missing required property store_id',
      ],
      [
        [
          'store_id' => 2,
        ],
        'Missing required property entity_id',
      ],
      [
        [
          'store_id' => 2,
          'entity_id' => 30,
        ],
        'Missing required property entity_type',
      ],
      [
        [
          'store_id' => 2,
          'entity_id' => 30,
          'entity_type' => 'commerce_product',
          'data' => 'INVALID',
          'created' => 1573344000,
        ],
        'The data property must be an array',
      ],
    ];
  }

  /**
   * Tests getters.
   *
   * @covers ::getStoreId
   * @covers ::getEntityId
   * @covers ::getEntityTypeId
   * @covers ::getLangcode
   * @covers ::getData
   * @covers ::getStatus
   * @covers ::getCreatedTime
   * @covers ::toArray
   */
  public function testGetters() {
    $definition = [
      'store_id' => $this->store->id(),
      'entity_id' => 30,
      'entity_type' => 'commerce_product',
    ];
    $store_override = new StoreOverride($definition);
    $this->assertEquals($definition['store_id'], $store_override->getStoreId());
    $this->assertEquals($definition['entity_id'], $store_override->getEntityId());
    $this->assertEquals($definition['entity_type'], $store_override->getEntityTypeId());
    $this->assertEquals(LanguageInterface::LANGCODE_DEFAULT, $store_override->getLangcode());
    $this->assertEquals([], $store_override->getData());
    $this->assertFalse($store_override->getStatus());
    $this->assertNull($store_override->getCreatedTime());

    $definition = [
      'store_id' => $this->store->id(),
      'entity_id' => 30,
      'entity_type' => 'commerce_product',
      'langcode' => 'fr',
      'data' => [
        'title' => ['value' => 'This is a custom title'],
      ],
      'status' => TRUE,
      'created' => 1573344000,
    ];
    $store_override = new StoreOverride($definition);
    $this->assertEquals($definition['store_id'], $store_override->getStoreId());
    $this->assertEquals($definition['entity_id'], $store_override->getEntityId());
    $this->assertEquals($definition['entity_type'], $store_override->getEntityTypeId());
    $this->assertEquals($definition['langcode'], $store_override->getLangcode());
    $this->assertEquals($definition['data'], $store_override->getData());
    $this->assertEquals($definition['status'], $store_override->getStatus());
    $this->assertEquals($definition['created'], $store_override->getCreatedTime());
    $this->assertEquals($definition, $store_override->toArray());
  }

  /**
   * Tests creating a store override from a store and an entity.
   *
   * @covers ::create
   */
  public function testCreate() {
    // @todo Also test creating an override from a product translation.
    $product = Product::create([
      'type' => 'default',
      'title' => 'Test',
    ]);
    $product->save();

    $definition = [
      'created' => 1573344000,
    ];
    $store_override = StoreOverride::create($this->store, $product, $definition);
    $this->assertEquals($this->store->id(), $store_override->getStoreId());
    $this->assertEquals($product->id(), $store_override->getEntityId());
    $this->assertEquals('commerce_product', $store_override->getEntityTypeId());
    $this->assertEquals(LanguageInterface::LANGCODE_DEFAULT, $store_override->getLangcode());
    $this->assertEquals($definition['created'], $store_override->getCreatedTime());
  }

}
