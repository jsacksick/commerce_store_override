<?php

namespace Drupal\commerce_store_override;

use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Represents a store override.
 */
final class StoreOverride {

  /**
   * The supported entity types.
   *
   * @var string[]
   */
  const SUPPORTED_ENTITY_TYPES = ['commerce_product'];

  /**
   * The store ID.
   *
   * @var int
   */
  protected $storeId;

  /**
   * The entity ID of the overridden entity.
   *
   * @var int
   */
  protected $entityId;

  /**
   * The entity type ID of the overridden entity.
   *
   * @var string
   */
  protected $entityType;

  /**
   * The language code of the overridden entity.
   *
   * @var string
   */
  protected $langcode;

  /**
   * The override data.
   *
   * @var array
   */
  protected $data = [];

  /**
   * The override status.
   *
   * @var bool
   */
  protected $status = FALSE;

  /**
   * The timestamp when the override was created.
   *
   * @var int
   */
  protected $created;

  /**
   * Constructs a new StoreOverride object.
   *
   * @param array $definition
   *   The definition.
   */
  public function __construct(array $definition) {
    foreach (['store_id', 'entity_id', 'entity_type'] as $required_property) {
      if (empty($definition[$required_property])) {
        throw new \InvalidArgumentException(sprintf('Missing required property %s.', $required_property));
      }
    }
    if (!in_array($definition['entity_type'], self::SUPPORTED_ENTITY_TYPES)) {
      throw new \InvalidArgumentException(sprintf('Unsupported entity type %s.', $definition['entity_type']));
    }
    if (!empty($definition['data']) && !is_array($definition['data'])) {
      throw new \InvalidArgumentException('The data property must be an array.');
    }

    $this->storeId = $definition['store_id'];
    $this->entityId = $definition['entity_id'];
    $this->entityType = $definition['entity_type'];
    $this->langcode = $definition['langcode'] ?? LanguageInterface::LANGCODE_DEFAULT;
    $this->data = $definition['data'] ?? [];
    $this->status = !empty($definition['status']);
    $this->created = $definition['created'] ?? NULL;
  }

  /**
   * Creates a StoreOverride object from the given store and entity.
   *
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   The store.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param array $definition
   *   The definition.
   *
   * @return static
   *   The store override.
   */
  public static function create(StoreInterface $store, ContentEntityInterface $entity, array $definition) {
    $definition['store_id'] = $store->id();
    $definition['entity_id'] = $entity->id();
    $definition['entity_type'] = $entity->getEntityTypeId();
    if (!$entity->isDefaultTranslation()) {
      $definition['langcode'] = $entity->language()->getId();
    }

    return new static($definition);
  }

  /**
   * Gets the store ID.
   *
   * @return int
   *   The store ID.
   */
  public function getStoreId() {
    return $this->storeId;
  }

  /**
   * Gets the entity ID of the overridden entity.
   *
   * @return int
   *   The entity ID.
   */
  public function getEntityId() {
    return $this->entityId;
  }

  /**
   * Gets the entity type ID of the overridden entity.
   *
   * @return string
   *   The entity type ID.
   */
  public function getEntityTypeId() {
    return $this->entityType;
  }

  /**
   * Gets the language code of the overridden entity.
   *
   * @return string
   *   The language code.
   */
  public function getLangcode() {
    return $this->langcode;
  }

  /**
   * Gets the override data.
   *
   * @return array
   *   The override data.
   */
  public function getData() {
    return $this->data;
  }

  /**
   * Gets the override status.
   *
   * @return bool
   *   TRUE if the override is active, FALSE otherwise.
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * Gets the timestamp when the override was created.
   *
   * @return int|null
   *   The timestamp when the override was created, or NULL if the timestamp
   *   should be generated when the override is saved.
   */
  public function getCreatedTime() {
    return $this->created;
  }

  /**
   * Gets the array representation of the store override.
   *
   * @return array
   *   The array representation of the store override.
   */
  public function toArray() {
    return [
      'store_id' => $this->storeId,
      'entity_id' => $this->entityId,
      'entity_type' => $this->entityType,
      'langcode' => $this->langcode,
      'data' => $this->data,
      'status' => $this->status,
      'created' => $this->created,
    ];
  }

  /**
   * Applies the override data to the given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   */
  public function apply(ContentEntityInterface $entity) {
    if ($this->entityType != $entity->getEntityTypeId()) {
      throw new \InvalidArgumentException(sprintf('Unexpected entity type %s.', $entity->getEntityTypeId()));
    }

    foreach ($this->data as $field_name => $value) {
      if ($entity->hasField($field_name)) {
        $entity->set($field_name, $value);
      }
    }
  }

}
