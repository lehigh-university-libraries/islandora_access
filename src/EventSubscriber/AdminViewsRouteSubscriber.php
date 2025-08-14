<?php

namespace Drupal\islandora_access\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Add custom access to the Islandora members/media view tabs.
 */
class AdminViewsRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('view.media_of.page_1')) {
      $route->setRequirements([
        '_custom_access' => '\Drupal\islandora_access\RouteAccess::checkAddMedia',
      ]);
    }
    if ($route = $collection->get('view.manage_members.page_1')) {
      $route->setRequirements([
        '_custom_access' => '\Drupal\islandora_access\RouteAccess::checkAddMembers',
      ]);
    }
  }

}
