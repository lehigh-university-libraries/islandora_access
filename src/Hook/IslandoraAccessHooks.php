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
    if ($node->isPublished()) {
      $grants[] = [
        'realm' => 'islandora_access_view_published',
        'gid' => 0,
        'grant_view' => 1,
        'grant_update' => 0,
        'grant_delete' => 0,
      ];
    }

    // If no one is an admin, no need to set any additional grants.
    if (!$node->hasField('field_administrator') || $node->field_administrator->isEmpty()) {
      return $grants;
    }

    $grants[] = [
      'realm' => "islandora_access_admin",
      'gid' => $node->id(),
      'grant_view' => 1,
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
    $grants = [
      'islandora_access_view_published' => [0],
    ];

    if ($account->isAnonymous()) {
      return $grants;
    }

    $nids = [];
    islandora_access_get_admins_nids($account->id(), $nids);
    $grants['islandora_access_admin'] = array_keys($nids);

    return $grants;
  }

}
