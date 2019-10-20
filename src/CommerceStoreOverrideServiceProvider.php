<?php

namespace Drupal\commerce_store_override;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;

class CommerceStoreOverrideServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if ($container->has('entity.repository')) {
      $definition = $container->getDefinition('entity.repository');
      $definition->setClass('\Drupal\commerce_store_override\EntityRepository');
    }
  }

}
