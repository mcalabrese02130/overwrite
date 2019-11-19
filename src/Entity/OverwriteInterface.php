<?php

namespace Drupal\overwrite\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 *
 */
interface OverwriteInterface extends ContentEntityInterface {

  /**
   * Setter function for the field value of the Overwrite entity .
   */
  public function setFieldValue($value);

  /**
   * Getter function for the field value of the Overwrite entity.
   */
  public function getFieldValue();

  /**
   * Gets the Entity Type of the entity that is having the Overwrite applied to.
   *
   * @return string
   *   Returns the entity type of the related entity.
   */
  public function getRelatedEntityType();

  /**
   * Gets the Entity Id of the entity that is having
   *  the Overwrite applied to.
   *
   * @return int
   *   Returns the entity id of the related entity.
   */
  public function getRelatedEntityId();

  /**
   * Setter function for the method of overwriting a field.
   *
   * @param string $method
   *   Valid values include:
   *   append
   *   prepend
   *   replace.
   */
  public function setMethod($method);

  /**
   * Getter function for the method of overwriting a field.
   *
   * @return string
   *   Returns the method.
   */
  public function getMethod();

  /**
   * Gets the Fieldname of the field of the entity that is having the Overwrite applied to.
   *
   * @return string
   *   Returns the field name that is overwritten.
   */
  public function getRelatedFieldname();

  /**
   * Gets the Field Definition information for the field of the entity that is having the Overwrite applied to.
   */
  public function getDefinitionOfField();

}
