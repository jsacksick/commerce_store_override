<?php

namespace Drupal\commerce_store_override\EventSubscriber;

use Drupal\commerce_store_override\Controller\StoreOverrideController;
use Drupal\commerce_store_override\StoreOverride;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Route;

class RouteSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new RouteSubscriber object.
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
  public static function getSubscribedEvents() {
    $events[RoutingEvents::DYNAMIC][] = ['onDynamicRouteEvent', 0];
    return $events;
  }

  /**
   * Generates routes for the override form.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The route build event.
   */
  public function onDynamicRouteEvent(RouteBuildEvent $event) {
    $route_collection = $event->getRouteCollection();
    foreach (StoreOverride::SUPPORTED_ENTITY_TYPES as $entity_type_id) {
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);

      $route = new Route($entity_type->getLinkTemplate('override-form'));
      $route
        ->setDefault('_entity_form', "{$entity_type_id}.override")
        ->setDefault('_title_callback', StoreOverrideController::class . '::title')
        ->setDefault('entity_type_id', $entity_type->id())
        ->setRequirement('_entity_access', "{$entity_type_id}.update")
        ->setRequirement($entity_type_id, '\d+')
        ->setRequirement('commerce_store', '\d+')
        ->setOption('parameters', [
          $entity_type_id => ['type' => 'entity:' . $entity_type_id],
          'commerce_store' => ['type' => 'entity:commerce_store'],
        ])
        ->setOption('_admin_route', TRUE);

      $route_collection->add("entity.{$entity_type_id}.override_form", $route);
    }
  }

}
