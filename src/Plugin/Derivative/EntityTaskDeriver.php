<?php

namespace Drupal\commerce_store_override\Plugin\Derivative;

use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\commerce_store_override\StoreOverride;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Derives local tasks for store overrides.
 *
 * Under the entity's "Edit" local task, a new "Master" local task is added,
 * linking to the original edit form, and a local task per store, linking
 * to the override form.
 *
 * Assumes that the entity type's local tasks are generated
 * by the Entity API module.
 */
class EntityTaskDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new EntityTaskDeriver object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $store_storage = $this->entityTypeManager->getStorage('commerce_store');
    $stores = $store_storage->loadMultiple();
    uasort($stores, function (StoreInterface $a, StoreInterface $b) {
      return strnatcasecmp($a->label(), $b->label());
    });

    foreach (StoreOverride::SUPPORTED_ENTITY_TYPES as $entity_type_id) {
      $this->derivatives[$entity_type_id . '.master'] = [
        'title' => $this->t('Master'),
        'route_name' => "entity.{$entity_type_id}.edit_form",
        'parent_id' => "entity.entity_tasks:entity.{$entity_type_id}.edit_form",
      ];
      foreach ($stores as $store) {
        $this->derivatives[$entity_type_id . '.' . $store->uuid()] = [
          'title' => $store->label(),
          'route_name' => "entity.{$entity_type_id}.override_form",
          'route_parameters' => [
            'commerce_store' => $store->id(),
          ],
          'parent_id' => "entity.entity_tasks:entity.{$entity_type_id}.edit_form",
        ];
      }
    }

    return $this->derivatives;
  }

}
