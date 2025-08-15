<?php

declare(strict_types=1);

namespace Drupal\islandora_access\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Order\OrderAfter;
use Drupal\node\Entity\Node;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Hook implementations for islandora_access module.
 */
class IslandoraAccessMedia {

  const ALLOWED_BUNDLES = ['file', 'image', 'audio', 'video', 'document'];

  /**
   * Implements hook_media_access().
   */
  #[Hook('media_access')]
  public function mediaAccess(EntityInterface $media, $op, AccountInterface $account) {
    if ($media->hasField('field_media_of')
      && !$media->field_media_of->isEmpty()
      && !is_null($media->field_media_of->entity)
      && $media->field_media_of->entity->access('update')) {
      return AccessResult::allowed();
    }

    if ($op == 'view') {
      return AccessResult::neutral();
    }

    return AccessResult::forbidden();
  }

  /**
   * Implements hook_media_create_access().
   */
  #[Hook('media_create_access')]
  public function createAccess(AccountInterface $account, array $context, $entity_bundle) {
    if (!in_array($entity_bundle, self::ALLOWED_BUNDLES)) {
      return AccessResult::neutral();
    }

    $nid = \Drupal::database()->query('SELECT entity_id FROM {node__field_administrator}
      WHERE field_administrator_target_id = :uid
      LIMIT 1', [
        ':uid' => $account->id(),
      ])->fetchField();

    return AccessResult::allowedIf($nid);
  }

  /**
   * Implements hook_form_alter().
   */
  #[Hook('form_alter', order: new OrderAfter(['islandora']))]
  public function formAlter(&$form, FormStateInterface $form_state, $form_id): void {
    if (\Drupal::requestStack()->getCurrentRequest()->getMethod() !== 'GET') {
      return;
    }

    // Do not apply to admins.
    if (in_array('administrator', \Drupal::currentUser()->getRoles())) {
      return;
    }

    if (!self::isMediaBundleForm($form_id)) {
      return;
    }

    // Do not allow editing the media of field.
    $form['field_media_of']['widget'][0]['target_id']['#attributes']['readonly'] = 'readonly';

    // If editing the form access is already checked in hook_media_access.
    if (substr($form_id, -9) === 'edit_form') {
      return;
    }

    $params = \Drupal::request()->query->all();
    if (!isset($params['edit']['field_media_of']['widget'][0]['target_id']) || !is_numeric($params['edit']['field_media_of']['widget'][0]['target_id'])) {
      $form['#access'] = FALSE;
      \Drupal::messenger()->addError('You do not have access to add this media.');
      return;
    }

    // Base media create/edit access on the parent node access.
    $parent = Node::load($params['edit']['field_media_of']['widget'][0]['target_id']);
    if (!$parent || !$parent->access('update')) {
      $form['#access'] = FALSE;
      \Drupal::messenger()->addError('You do not have access to add this media.');
    }
  }

  /**
   * Check if form_id is an Islandora media add/edit form.
   */
  protected static function isMediaBundleForm($form_id) {
    $bundles = implode('|', self::ALLOWED_BUNDLES);

    return preg_match('/^media_(' . $bundles . ')_(edit|add)_form$/', $form_id);
  }

}
