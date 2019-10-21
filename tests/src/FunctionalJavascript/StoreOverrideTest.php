<?php

namespace Drupal\Tests\commerce_store_override\FunctionalJavascript;

use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_store_override\StoreOverride;
use Drupal\Tests\commerce_product\FunctionalJavascript\ProductWebDriverTestBase;

/**
 * Tests the override UI.
 *
 * @group commerce
 */
class StoreOverrideTest extends ProductWebDriverTestBase {

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
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_store_override',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->repository = $this->container->get('commerce_store_override.repository');

    // Shows the variation title by default, which is not needed here.
    $product_view_display = commerce_get_entity_display('commerce_product', 'default', 'view');
    $product_view_display->removeComponent('variations');
    $product_view_display->save();

    // Assign user-friendly labels to each store.
    $this->stores[0]->set('name', 'Sweden');
    $this->stores[1]->set('name', 'Norway');
    $this->stores[2]->set('name', 'Finland');
    foreach ($this->stores as $store) {
      $store->save();
    }
    // Rebuild the product local tasks.
    \Drupal::service('router.builder')->rebuild();

    $variation = ProductVariation::create([
      'type' => 'default',
      'sku' => 'TEST',
      'price' => [
        'number' => '30.00',
        'currency_code' => 'USD',
      ],
    ]);
    $variation->save();

    $product = Product::create([
      'type' => 'default',
      'title' => 'Test (Master)',
      'variations' => [$variation],
    ]);
    $product->save();
    $this->product = $product;
  }

  /**
   * Tests adding an override.
   */
  public function testAdd() {
    $this->drupalGet($this->product->toUrl('edit-form'));
    $this->assertSession()->linkExists('Master');
    foreach ($this->stores as $store) {
      $this->assertSession()->linkExists($store->label());
    }

    $this->getSession()->getPage()->clickLink('Finland');
    $this->assertSession()->fieldExists('data[title][0][value]');
    $this->assertSession()->fieldExists('status');

    $this->submitForm([
      'data[title][0][value]' => 'Test (Finland)',
      'status' => TRUE,
    ], 'Save');
    $this->assertSession()->pageTextContains('Saved Test (Finland).');

    // Confirm that the store override has the expected data.
    $store_override = $this->repository->load($this->stores[2], $this->product);
    $this->assertNotNull($store_override);
    $this->assertEquals($this->stores[2]->id(), $store_override->getStoreId());
    $this->assertEquals($this->product->id(), $store_override->getEntityId());
    $this->assertEquals('commerce_product', $store_override->getEntityTypeId());
    $this->assertEquals([
      'title' => ['value' => 'Test (Finland)'],
    ], $store_override->getData());
    $this->assertTrue($store_override->getStatus());

    // Confirm that the override data is not present on the Master form.
    $this->drupalGet($this->product->toUrl('edit-form'));
    $this->assertSession()->fieldValueEquals('title[0][value]', 'Test (Master)');

    // Override the variation as well.
    $variation = $this->product->getDefaultVariation();
    $this->drupalGet($variation->toUrl('edit-form'));
    $this->getSession()->getPage()->clickLink('Finland');
    $this->submitForm([
      'data[price][0][number]' => '40',
      'status' => TRUE,
    ], 'Save');
    $this->assertSession()->pageTextContains('Saved Test (Master).');

    // Confirm that the rendered product shows overridden data.
    $this->drupalGet($this->product->toUrl('canonical'));
    $this->assertSession()->pageTextContains('Test (Finland)');
    $this->assertSession()->pageTextNotContains('Test (Master)');
    $this->assertSession()->pageTextContains('$40');
    $this->assertSession()->pageTextNotContains('$30');
  }

  /**
   * Tests editing an override.
   */
  public function testEdit() {
    $definition = [
      'data' => [
        'title' => [
          'value' => 'Test (Sweden)',
        ],
      ],
      'status' => FALSE,
    ];
    $store_override = StoreOverride::create($this->stores[0], $this->product, $definition);
    $this->repository->save($store_override);

    $definition = [
      'data' => [
        'title' => [
          'value' => 'Test (Norway)',
        ],
      ],
      'status' => FALSE,
    ];
    $store_override = StoreOverride::create($this->stores[1], $this->product, $definition);
    $this->repository->save($store_override);

    // Confirm that the override for Sweden can be edited.
    $this->drupalGet($this->product->toUrl('edit-form'));
    $this->getSession()->getPage()->clickLink('Sweden');
    $this->assertSession()->fieldValueEquals('data[title][0][value]', 'Test (Sweden)');
    $this->assertSession()->checkboxNotChecked('status');

    $this->submitForm([
      'data[title][0][value]' => 'Test2 (Sweden)',
      'status' => TRUE,
    ], 'Save');
    $this->assertSession()->pageTextContains('Saved Test2 (Sweden).');

    $store_override = $this->repository->load($this->stores[0], $this->product);
    $this->assertNotNull($store_override);
    $this->assertEquals([
      'title' => ['value' => 'Test2 (Sweden)'],
    ], $store_override->getData());
    $this->assertTrue($store_override->getStatus());

    // Confirm that the other two tabs have the expected values.
    $this->getSession()->getPage()->clickLink('Norway');
    $this->assertSession()->fieldValueEquals('data[title][0][value]', 'Test (Norway)');
    $this->assertSession()->checkboxNotChecked('status');

    $this->getSession()->getPage()->clickLink('Finland');
    $this->assertSession()->fieldValueEquals('data[title][0][value]', 'Test (Master)');
    $this->assertSession()->checkboxChecked('status');
  }

}
