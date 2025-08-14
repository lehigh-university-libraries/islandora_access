# Islandora Access

## Introduction

Assign administrators to parent items to easily assign view, update, and delete accesss to islandora objects and their children.

Requires a field `field_administrator` attached to a node that points to user entities. When editing a node in Islandora, if you reference a user account with the `field_administrator` on the node, that account will be able to view, update, and delete that node, and any children nodes that reference that node with `field_member_of`.


## Requirements

This module is intended to work with [Islandora][1]

Requires three fields on a node:

- `field_member_of` - entity reference to a node entity
- `field_model` - entity reference to a term entity
- `field_administrator` - entity reference to a user entity

## Installation

Install as usual, see
[this](https://www.drupal.org/docs/extending-drupal/installing-modules) for
further information.

## Configuration

This module provides no configuration beyond ensuring the three required fields are present on the content type(s) you want this module to control access for.

[1]: https://github.com/islandora/islandora
