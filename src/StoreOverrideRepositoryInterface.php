<?php

namespace Drupal\commerce_store_override;

use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\Core\Entity\ContentEntityInterface;

interface StoreOverrideRepositoryInterface {

  /**
   * Loads the store override for the given store and entity.
   *
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   The store.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\commerce_store_override\StoreOverride|null
   *   The store override, or NULL if none found.
   */
  public function load(StoreInterface $store, ContentEntityInterface $entity);

  /**
   * Loads all store overrides for the given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\commerce_store_override\StoreOverride[]
   *   The store overrides.
   */
  public function loadMultipleByEntity(ContentEntityInterface $entity);

  /**
   * Saves the given store override.
   *
   * @param \Drupal\commerce_store_override\StoreOverride $store_override
   *   The store override.
   */
  public function save(StoreOverride $store_override);

  /**
   * Deletes the given store override.
   *
   * @param \Drupal\commerce_store_override\StoreOverride $store_override
   *   The store override.
   */
  public function delete(StoreOverride $store_override);

  /**
   * Deletes all store overrides for the given store.
   *
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   The store.
   */
  public function deleteMultipleByStore(StoreInterface $store);

  /**
   * Delete all store overrides for the given entity.
   *
   * When a translation is given, only overrides for that translation will
   * be deleted.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   */
  public function deleteMultipleByEntity(ContentEntityInterface $entity);

}
