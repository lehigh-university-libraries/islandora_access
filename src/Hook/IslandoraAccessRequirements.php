<?php

declare(strict_types=1);

namespace Drupal\islandora_access\Hook;

use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Core\Url;

/**
 * Hook implementations for islandora_access module.
 */
class IslandoraAccessRequirements {

  /**
   * Implements hook_requirements().
   *
   * Hopefully this will eventually just be the hook attribute.
   *
   * @see https://api.drupal.org/api/drupal/core%21core.api.php/group/hooks/11.x
   */
  public static function check($phase) : array {
    $requirements = [];

    // Only check during runtime (status report page)
    if ($phase == 'runtime') {
      $requirements['islandora_access_field_administrator'] = self::checkAdminField();
    }

    return $requirements;
  }

  /**
   * Checks if the required field_administrator field exists.
   */
  public static function checkAdminField() {
    $requirement = [
      'title' => t('Islandora Access: Administrator Field'),
    ];

    $schema = \Drupal::database()->schema();

    if (!$schema->tableExists('node__field_administrator')) {
      $requirement['severity'] = RequirementSeverity::Error;
      $requirement['value'] = t('Missing required field');
      $requirement['description'] = t('You must create a user entity reference field with the machine name "field_administrator" on content types to use the Islandora Access module.');

      return $requirement;
    }

    // Check if field is actually attached to any content types.
    $field_configs = \Drupal::entityTypeManager()
      ->getStorage('field_config')
      ->loadByProperties([
        'field_name' => 'field_administrator',
        'entity_type' => 'node',
        'field_type' => 'entity_reference',
      ]);

    if (empty($field_configs)) {
      $requirement['severity'] = RequirementSeverity::Error;
      $requirement['value'] = t('Field not attached to any content types');
      $requirement['description'] = t('The field_administrator field storage exists but is not attached to any content types or is not an entity reference field. <a href="@manage_fields">Manage fields</a> to add it to content types where administrators should be assigned.', [
        '@manage_fields' => Url::fromRoute('entity.node_type.collection')->toString(),
      ]);
      return $requirement;
    }

    $attached_bundles = [];
    foreach ($field_configs as $field_config) {
      $bundle = $field_config->getTargetBundle();
      $node_type = \Drupal::entityTypeManager()
        ->getStorage('node_type')
        ->load($bundle);
      $attached_bundles[] = $node_type ? $node_type->label() : $bundle;
    }

    $requirement['severity'] = RequirementSeverity::OK;
    $requirement['value'] = t('Configured correctly');
    $requirement['description'] = t('The field_administrator field is properly configured and attached to the following content types: @bundles', [
      '@bundles' => implode(', ', $attached_bundles),
    ]);

    return $requirement;
  }

}
