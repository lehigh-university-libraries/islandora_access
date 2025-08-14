<?php

namespace Drupal\Tests\islandora_access\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;
use Drupal\link\LinkItemInterface;
use Drupal\node\Entity\Node;

/**
 * Functional tests for field dependency integration.
 *
 * Tests the actual behavior of the module with real field data
 * and user interactions, focusing on the module's assumptions.
 *
 * @group islandora_access
 */
class IslandoraAccessFieldIntegrationTest extends BrowserTestBase {

  use EntityReferenceFieldCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'user',
    'field',
    'taxonomy',
    'link',
    'text',
    'islandora_access',
  ];

  /**
   * Test user who will be an administrator.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Test user without administrative privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $regularUser;

  /**
   * Collection taxonomy term.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $collectionTerm;

  /**
   * Image taxonomy term.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $imageTerm;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->container->get('entity_type.manager')
      ->getStorage('node_type')
      ->create([
        'type' => 'islandora_object',
        'name' => 'Repository Object',
      ])->save();
    $this->createEntityReferenceField('node', 'islandora_object', 'field_member_of', 'Member Of', 'node', 'default', [], -1);
    $this->createEntityReferenceField('node', 'islandora_object', 'field_model', 'Model', 'taxonomy_term', 'default', [], -1);
    $this->createEntityReferenceField('node', 'islandora_object', 'field_administrator', 'Administrator', 'user', 'default', [], -1);

    $modelVocab = $this->container->get('entity_type.manager')
      ->getStorage('taxonomy_vocabulary')
      ->create([
        'name' => 'Islandora Model',
        'vid' => 'islandora_models',
      ]);
    $modelVocab->save();
    $fieldStorage = $this->container->get('entity_type.manager')
      ->getStorage('field_storage_config')
      ->create([
        'field_name' => 'field_external_uri',
        'entity_type' => 'taxonomy_term',
        'type' => 'link',
      ]);
    $fieldStorage->save();
    $field = $this->container->get('entity_type.manager')
      ->getStorage('field_config')
      ->create([
        'field_storage' => $fieldStorage,
        'bundle' => $modelVocab->id(),
        'settings' => [
          'title' => 'External URI',
          'link_type' => LinkItemInterface::LINK_EXTERNAL,
        ],
      ]);
    $field->save();
    // Collection model term.
    $this->collectionTerm = $this->container->get('entity_type.manager')
      ->getStorage('taxonomy_term')
      ->create([
        'vid' => 'islandora_models',
        'name' => 'Collection',
        'field_external_uri' => [
          'uri' => 'http://purl.org/dc/dcmitype/Collection',
        ],
      ]);
    $this->collectionTerm->save();

    // Image model term (non-collection).
    $this->imageTerm = $this->container->get('entity_type.manager')
      ->getStorage('taxonomy_term')
      ->create([
        'vid' => 'islandora_models',
        'name' => 'Image',
        'field_external_uri' => [
          'uri' => 'http://purl.org/dc/dcmitype/Image',
        ],
      ]);
    $this->imageTerm->save();

    $this->adminUser = $this->drupalCreateUser();
    $this->regularUser = $this->drupalCreateUser();
  }

  /**
   * Test that field_administrator controls access properly.
   */
  public function testFieldAdministratorAccessControl() {
    // Create a node with adminUser as administrator.
    $node = Node::create([
      'type' => 'islandora_object',
      'title' => 'Test Repository Object',
      'field_administrator' => $this->adminUser->id(),
      'field_model' => $this->collectionTerm->id(),
      'status' => 0,
    ]);
    $node->save();

    $this->assertTrue($node->access('view', $this->adminUser), 'Admin should be able to update node');
    $this->assertFalse($node->access('view', $this->regularUser), 'Regular user should not be able to update node');

    $this->assertTrue($node->access('update', $this->adminUser), 'Admin should be able to update node');
    $this->assertFalse($node->access('update', $this->regularUser), 'Regular user should not be able to update node');

    $node->set('field_administrator', NULL);
    $node->save();
  }

  /**
   * Test that changing field_administrator changes access.
   */
  public function testChangingAdministratorChangesAccess() {
    // Create node with admin user as administrator.
    $node = Node::create([
      'type' => 'islandora_object',
      'title' => 'Changing Admin Test',
      'field_administrator' => $this->adminUser->id(),
      'field_model' => $this->collectionTerm->id(),
      'status' => 0,
    ]);
    $node->save();

    // Verify admin has access.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->statusCodeEquals(200);

    // Change administrator to regular user.
    $node->set('field_administrator', $this->regularUser->id());
    $node->save();

    // Admin should no longer have special access.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalLogin($this->regularUser);
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Test field_model collection detection integration.
   */
  public function testFieldModelCollectionDetection() {
    // Create collection node.
    $collection = Node::create([
      'type' => 'islandora_object',
      'title' => 'Test Collection',
      'field_model' => $this->collectionTerm->id(),
      'field_administrator' => $this->adminUser->id(),
      'status' => 0,
    ]);
    $collection->save();

    // Create non-collection node.
    $image = Node::create([
      'type' => 'islandora_object',
      'title' => 'Test Image',
      'field_model' => $this->imageTerm->id(),
      'field_administrator' => $this->adminUser->id(),
      'status' => 0,
    ]);
    $image->save();

    // Test collection detection.
    $this->assertTrue(
      islandora_access_node_is_collection($collection),
      'Node with Collection model should be detected as collection.'
    );

    $this->assertFalse(
      islandora_access_node_is_collection($image),
      'Node with Image model should not be detected as collection.'
    );
  }

  /**
   * Test field_member_of hierarchy behavior.
   */
  public function testFieldMemberOfHierarchy() {
    // Create parent collection.
    $parentCollection = Node::create([
      'type' => 'islandora_object',
      'title' => 'Parent Collection',
      'field_model' => $this->collectionTerm->id(),
      'field_administrator' => $this->adminUser->id(),
      'status' => 0,
    ]);
    $parentCollection->save();

    // Create child collection.
    $childCollection = Node::create([
      'type' => 'islandora_object',
      'title' => 'Child Collection',
      'field_model' => $this->collectionTerm->id(),
      'field_member_of' => $parentCollection->id(),
      'status' => 0,
    ]);
    $childCollection->save();

    // Create grandchild object.
    $grandchild = Node::create([
      'type' => 'islandora_object',
      'title' => 'Grandchild Object',
      'field_member_of' => $childCollection->id(),
      'status' => 0,
    ]);
    $grandchild->save();

    // Test that admin user has access to all levels through hierarchy.
    $nids = [];
    islandora_access_get_admins_nids($this->adminUser->id(), $nids);

    $this->assertContains(
      $parentCollection->id(),
      $nids,
      'Admin should have access to parent collection.'
    );

    $this->assertContains(
      $childCollection->id(),
      $nids,
      'Admin should have access to child collection through hierarchy.'
    );

    $this->assertContains(
      $grandchild->id(),
      $nids,
      'Admin should have access to grandchild through hierarchy.'
    );
  }

  /**
   * Test multiple administrators functionality.
   */
  public function testMultipleAdministrators() {
    // Create additional test user.
    $secondAdmin = $this->drupalCreateUser([
      'access content',
    ]);

    // Create node with multiple administrators.
    $node = Node::create([
      'type' => 'islandora_object',
      'title' => 'Multi-Admin Object',
      'field_administrator' => [
        $this->adminUser->id(),
        $secondAdmin->id(),
      ],
      'status' => 0,
    ]);
    $node->save();

    // Both administrators should have access.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalLogin($secondAdmin);
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->statusCodeEquals(200);

    // Regular user should not have access.
    $this->drupalLogin($this->regularUser);
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Test self reference doesn't cause infinite recursion.
   */
  public function testInfiniteRecursion() {
    $node = Node::create([
      'type' => 'islandora_object',
      'title' => 'Test Object',
      'field_model' => $this->collectionTerm->id(),
      'field_administrator' => $this->adminUser->id(),
      'status' => 0,
    ]);
    $node->save();
    $node->set('field_member_of', $node->id());
    $node->save();

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Test a bad field member of doesn't break this code.
   */
  public function testBadReference() {
    // Create node with multiple administrators.
    $node = Node::create([
      'type' => 'islandora_object',
      'title' => 'Test Object',
      'field_administrator' => $this->adminUser->id(),
      'field_member_of' => 99999999,
      'field_model' => 9999999,
      'status' => 0,
    ]);
    $node->save();

    // Both administrators should have access.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->statusCodeEquals(200);
  }

}
