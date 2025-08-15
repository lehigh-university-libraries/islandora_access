<?php

declare(strict_types=1);

namespace Drupal\islandora_access\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Hook implementations for islandora_access module.
 */
class IslandoraAccessHooks {

  /**
   * Implements hook_node_access_records().
   */
  #[Hook('node_access_records')]
  public function nodeAccessRecords(NodeInterface $node): array {
    $grants = [];
    if (!$node->hasField('field_administrator')) {
      return $grants;
    }

    $grants[] = [
      'realm' => "islandora_access_admin",
      'gid' => $node->id(),
      // Only override view grants for unpublished nodes.
      'grant_view' => $node->isPublished() ? 0 : 1,
      'grant_update' => 1,
      'grant_delete' => 1,
      'priority' => 0,
    ];

    return $grants;
  }

  /**
   * Implements hook_node_grants().
   */
  #[Hook('node_grants')]
  public function nodeGrants(AccountInterface $account, $op): array {
    $nids = [];
    islandora_access_get_admins_nids($account->id(), $nids);
    $grants = [
      'islandora_access_admin' => array_keys($nids),
    ];

    return $grants;
  }

}
