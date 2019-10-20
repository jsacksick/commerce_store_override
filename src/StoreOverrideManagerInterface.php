<?php

namespace Drupal\commerce_store_override;

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

}
