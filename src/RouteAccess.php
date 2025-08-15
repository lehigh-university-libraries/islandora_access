<?php

namespace Drupal\islandora_access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;

/**
 * Custom route access functions for Islandora node tabs.
 */
class RouteAccess {

  /**
   * Check if an account can access the members tab for a node.
   */
  public function checkAddMembers(AccountInterface $account) : AccessResult {
    if ($account->isAnonymous()) {
      return AccessResult::forbidden();
    }

    // While we're at it, hide the members tab if this isn't a collection.
    $node = self::getRouteNode();
    $allowed = islandora_access_node_is_collection($node->id());
    if (!$allowed) {
      return AccessResult::forbidden();
    }

    // Only show members tab if they can update the current node.
    return self::canUpdateCurrentNode();
  }

  /**
   * Check if an account can access the media tab for a node.
   */
  public function checkAddMedia(AccountInterface $account) : AccessResult {
    if ($account->isAnonymous()) {
      return AccessResult::forbidden();
    }

    if ($account->hasPermission('manage media')) {
      return AccessResult::allowed();
    }

    return self::canUpdateCurrentNode();
  }

  /**
   * Get the node currently being viewed.
   */
  public static function getRouteNode() {
    $nid = \Drupal::routeMatch()->getParameter('node');

    return is_string($nid) ? Node::load($nid) : $nid;
  }

  /**
   * Check if the node a user is currently visiting can be edited by the user.
   */
  public static function canUpdateCurrentNode() : AccessResult {
    $node = self::getRouteNode();

    return AccessResult::allowedIf($node && $node->access('update'));
  }

}
