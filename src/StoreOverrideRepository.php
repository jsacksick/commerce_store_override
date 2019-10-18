<?php

namespace Drupal\commerce_store_override;

use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Language\LanguageInterface;

class StoreOverrideRepository implements StoreOverrideRepositoryInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new StoreOverrideRepository object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time.
   */
  public function __construct(Connection $connection, TimeInterface $time) {
    $this->connection = $connection;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public function load(StoreInterface $store, ContentEntityInterface $entity) {
    $langcode = LanguageInterface::LANGCODE_DEFAULT;
    if (!$entity->isDefaultTranslation()) {
      $langcode = $entity->language()->getId();
    }

    $query = $this->connection->select('commerce_store_override')
      ->fields('commerce_store_override')
      ->condition('store_id', $store->id())
      ->condition('entity_id', $entity->id())
      ->condition('entity_type', $entity->getEntityTypeId())
      ->condition('langcode', $langcode);
    $result = $query->execute()->fetchAssoc();
    $store_override = NULL;
    if ($result) {
      $result['data'] = json_decode($result['data'], TRUE);
      $store_override = new StoreOverride($result);
    }

    return $store_override;
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultipleByEntity(ContentEntityInterface $entity) {
    $langcode = LanguageInterface::LANGCODE_DEFAULT;
    if (!$entity->isDefaultTranslation()) {
      $langcode = $entity->language()->getId();
    }

    $store_overrides = [];
    $query = $this->connection->select('commerce_store_override')
      ->fields('commerce_store_override')
      ->condition('entity_id', $entity->id())
      ->condition('entity_type', $entity->getEntityTypeId())
      ->condition('langcode', $langcode);
    $results = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
    foreach ($results as $result) {
      $result['data'] = json_decode($result['data'], TRUE);
      $store_overrides[] = new StoreOverride($result);
    }

    return $store_overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function save(StoreOverride $store_override) {
    $definition = $store_override->toArray();
    $definition['data'] = json_encode($definition['data']);
    $definition['status'] = (int) $definition['status'];
    if (empty($definition['created'])) {
      $definition['created'] = $this->time->getRequestTime();
    }

    $key_names = ['store_id', 'entity_id', 'entity_type', 'langcode'];
    $keys = array_intersect_key($definition, array_combine($key_names, $key_names));

    $this->connection->merge('commerce_store_override')
      ->keys($keys)
      ->fields($definition)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function delete(StoreOverride $store_override) {
    $this->connection->delete('commerce_store_override')
      ->condition('store_id', $store_override->getStoreId())
      ->condition('entity_id', $store_override->getEntityId())
      ->condition('entity_type', $store_override->getEntityTypeId())
      ->condition('langcode', $store_override->getLangcode())
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteByStore(StoreInterface $store) {
    $this->connection->delete('commerce_store_override')
      ->condition('store_id', $store->id())
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteByEntity(ContentEntityInterface $entity) {
    $query = $this->connection->delete('commerce_store_override');
    $query
      ->condition('entity_id', $entity->id())
      ->condition('entity_type', $entity->getEntityTypeId());
    if (!$entity->isDefaultTranslation()) {
      // Delete only the override for the given translation.
      $query->condition('langcode', $entity->language()->getId());
    }
    $query->execute();
  }

}
