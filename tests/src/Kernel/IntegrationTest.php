<?php

namespace Drupal\Tests\commerce_store_override\Kernel;

use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the integration with products and stores.
 *
 * @group commerce
 */
class IntegrationTest extends CommerceKernelTestBase {

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
   * Confirms that deleting a store deletes matching overrides.
   */
  public function testStoreDelete() {
    // @todo
  }

  /**
   * Confirms that deleting an entity deletes matching overrides.
   */
  public function testEntityDelete() {
    // @todo
  }

  /**
   * Confirms that deleting an entity translation deletes matching overrides.
   */
  public function testEntityTranslationDelete() {
    // @todo
  }

}
