<?php

namespace Drupal\commerce_store_override;

use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Routing\RouteMatchInterface;

class StoreOverrideManager implements StoreOverrideManagerInterface {

  /**
   * The current store.
   *
   * @var \Drupal\commerce_store\CurrentStoreInterface
   */
  protected $currentStore;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The store override repository.
   *
   * @var \Drupal\commerce_store_override\StoreOverrideRepositoryInterface
   */
  protected $repository;

  /**
   * The entity field manager
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity type manager
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new StoreOverrideManager object.
   *
   * @param \Drupal\commerce_store\CurrentStoreInterface $current_store
   *   The current store.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\commerce_store_override\StoreOverrideRepositoryInterface $repository
   *   The store override repository.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(CurrentStoreInterface $current_store, RouteMatchInterface $route_match, StoreOverrideRepositoryInterface $repository, EntityFieldManagerInterface $entity_field_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->currentStore = $current_store;
    $this->routeMatch = $route_match;
    $this->repository = $repository;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function shouldOverride(ContentEntityInterface $entity) {
    if (!in_array($entity->getEntityTypeId(), StoreOverride::SUPPORTED_ENTITY_TYPES)) {
      return FALSE;
    }
    $route = $this->routeMatch->getRouteObject();
    if (!$route) {
      // The route isn't available yet (method called during route enhancing).
      return FALSE;
    }
    if ($route->getOption('_admin_route')) {
      // It is assumed that admin routes are used for editing data, in
      // which case the original (master) data should always be shown.
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function override(ContentEntityInterface $entity) {
    $store = $this->currentStore->getStore();
    $store_override = $this->repository->load($store, $entity);
    if ($store_override && $store_override->getStatus()) {
      $store_override->apply($entity);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAllowedFieldsOverride(ConfigEntityInterface $bundle_entity) {
    // Get the entity type this bundle entity is the bundle of.
    $entity_type = $this->entityTypeManager->getDefinition($bundle_entity->getEntityType()->getBundleOf());
    $storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions($entity_type->id());
    $fields = [];

    foreach ($this->entityFieldManager->getFieldDefinitions($entity_type->id(), $bundle_entity->id()) as $field_name => $definition) {
      // Filter out some fields that can't be overridden.
      if (!isset($storage_definitions[$field_name]) || !$this->fieldIsAllowed($entity_type, $storage_definitions[$field_name])) {
        continue;
      }
      $fields[$field_name] = $definition->getLabel();
    }

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getEnabledFieldsOverride(ConfigEntityInterface $bundle_entity) {
    $store_override_settings = $bundle_entity->getThirdPartySettings('commerce_store_override');
    return !empty($store_override_settings['fields']) ? $store_override_settings['fields'] : [];
  }

  /**
   * Checks whether a field can be overridden.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $definition
   *   The field storage definition.
   *
   * @return bool
   *   TRUE if field a field can be overridden, FALSE otherwise.
   */
  protected function fieldIsAllowed(EntityTypeInterface $entity_type, FieldStorageDefinitionInterface $definition) {
    return $definition->getProvider() != 'content_translation' &&
      !in_array($definition->getName(), [$entity_type->getKey('langcode'), $entity_type->getKey('default_langcode'), 'revision_translation_affected']);
  }

}
