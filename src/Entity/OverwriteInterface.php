<?php

namespace Drupal\overwrite\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface for defining Linkwell site group entities.
 *
 * @ingroup linkwell_sites
 */
interface OverwriteInterface extends  ContentEntityInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Linkwell site group name.
   *
   * @return string
   *   Name of the Linkwell site group.
   */

  public function setFieldValue($value);
  public function getFieldValue();
  public function getRelatedEntityType();
  public function getRelatedEntityId();
  /**
   * Sets the Linkwell site group name.
   *
   * @param string $name
   *   The Linkwell site group name.
   *
   * @return \Drupal\linkwell_sites\Entity\LinkwellSiteGroupInterface
   *   The called Linkwell site group entity.
   */
  public function setMethod($method);
  
  public function getMethod();

  public function getRelatedFieldname();

  public function getDefinitionOfField();
}
