<?php

namespace Drupal\commerce_store_override\Controller;

use Drupal\Core\Routing\RouteMatchInterface;

class StoreOverrideController {

  /**
   * Builds a title for the override form.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return string
   *   The edit title.
   */
  public function title(RouteMatchInterface $route_match) {
    $store = $route_match->getParameter('commerce_store');
    return $store->label();
  }

}
