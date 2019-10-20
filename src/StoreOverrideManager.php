<?php

namespace Drupal\commerce_store_override;

use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\Core\Entity\ContentEntityInterface;
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
   * Constructs a new StoreOverrideManager object.
   *
   * @param \Drupal\commerce_store\CurrentStoreInterface $current_store
   *   The current store.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\commerce_store_override\StoreOverrideRepositoryInterface $repository
   *   The store override repository.
   */
  public function __construct(CurrentStoreInterface $current_store, RouteMatchInterface $route_match, StoreOverrideRepositoryInterface $repository) {
    $this->currentStore = $current_store;
    $this->routeMatch = $route_match;
    $this->repository = $repository;
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
    if ($store_override) {
      $store_override->apply($entity);
    }
  }

}
