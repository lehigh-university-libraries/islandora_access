# Islandora Access

## Introduction

Assign administrators to parent items to easily assign view, update, and delete accesss to islandora objects, their children items, and media.

Requires a field `field_administrator` attached to a node that points to user entities. When editing a node in Islandora, if you reference a user account with the `field_administrator` on the node, that account will be able to view, update, and delete that node, and any children nodes that reference that node with `field_member_of`.

Node administrators are then also able to create, update, and delete media for the node(s) the user is an administator of.

## Requirements

This module is intended to work with [Islandora][1]

Requires three fields on a node:

- `field_member_of` - entity reference to a node entity
- `field_model` - entity reference to a term entity
  - The term entity then needs `field_external_uri` to identify collection nodes
- `field_administrator` - entity reference to a user entity
  - this field is what assigns editors to nodes and their children

One optional field on media to also have media access controlled by this module:

- Media with `field_media_of` set to nodes to control media access with this module

## Installation

```
composer require drupal/islandora_access
```

## Configuration

This module provides no configuration beyond ensuring the three required fields are present on the content type(s) you want this module to control access for.

[1]: https://github.com/islandora/islandora
