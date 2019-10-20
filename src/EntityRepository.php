<?php

namespace Drupal\commerce_store_override;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepository as CoreEntityRepository;

/**
 * Extends getTranslationFromContext() to apply store overrides.
 *
 * This method is used to prepare an entity for rendering, allowing
 * our module to apply an override without affecting the entity in
 * other contexts (such as editing).
 *
 * Note that StoreOverrideManager has an additional safeguard, where
 * it forbids overriding on admin routes.
 *
 * @see \Drupal\commerce_store_override\StoreOverrideManager::shouldOverride()
 */
class EntityRepository extends CoreEntityRepository {

  /**
   * {@inheritdoc}
   */
  public function getTranslationFromContext(EntityInterface $entity, $langcode = NULL, $context = []) {
    $entity = parent::getTranslationFromContext($entity, $langcode, $context);

    if ($entity instanceof ContentEntityInterface) {
      // Not injected to avoid a ServiceCircularReferenceException
      // caused by StoreOverrideManager's CurrentStore indirectly
      // leading back to EntityRepository.
      $override_manager = \Drupal::service('commerce_store_override.manager');
      if ($override_manager->shouldOverride($entity)) {
        $override_manager->override($entity);
      }
    }

    return $entity;
  }

}
