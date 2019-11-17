<?php

namespace Drupal\commerce_store_override;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\ContentEntityInterface;

interface StoreOverrideManagerInterface {

  /**
   * Checks whether the given entity should be overridden.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   *
   * @return bool
   *   TRUE if the entity should be overridden, FALSE otherwise.
   */
  public function shouldOverride(ContentEntityInterface $entity);

  /**
   * Overrides the given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   */
  public function override(ContentEntityInterface $entity);

  /**
   * Get the list of fields that can be overridden.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $bundle_entity
   *   The bundle entity.
   *
   * @return array
   *   The list of fields that can be overridden.
   */
  public function getAllowedFieldsOverride(ConfigEntityInterface $bundle_entity);

  /**
   * Get the list of fields that are configured for overrides.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $bundle_entity
   *   The bundle entity.
   *
   * @return array
   *   The list of fields that are configured for overrides.
   */
  public function getEnabledFieldsOverride(ConfigEntityInterface $bundle_entity);

}
