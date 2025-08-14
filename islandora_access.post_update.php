<?php

/**
 * @file
 * Post updates.
 */

/**
 * Trigger node access rebuild after install.
 */
function islandora_access_post_update_node_access_rebuild() {
  node_access_needs_rebuild(TRUE);
}
