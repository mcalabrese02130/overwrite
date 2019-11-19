<?php

namespace Drupal\overwrite\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

interface OverwriteInterface extends  ContentEntityInterface {

  /**
   * Setter function for the field value of the Overwrite entity 
   **/
  public function setFieldValue($value);

  /**
   *  Getter function for the field value of the Overwrite entity
   **/
  public function getFieldValue();

  /**
   *  Gets the Entity Type of the entity that is having
   *  the Overwrite applied to.
   *
   *  @return string
   **/
  public function getRelatedEntityType();

  /**
   *  Gets the Entity Id of the entity that is having
   *  the Overwrite applied to.
   *
   *  @return int
   **/
  public function getRelatedEntityId();
  
  /**
   *  Setter function for the method of overwriting a field
   *  
   *  @param string $method
   *  valid values include:
   *   append
   *   prepend
   *   replace
   *   
   **/
  public function setMethod($method);
   
  /**
   *  Getter function for the method of overwriting a field
   *
   *  @return string
   **/
  public function getMethod();
 
  /**
   *  Gets the Fieldname of the field of the entity that
   *  is having the Overwrite applied to.
   *
   *  @return string
   **/
  public function getRelatedFieldname();
 
  /**
   *  Gets the Field Definition information for the field
   *  of the entity that is having the Overwrite applied to.
   **/
  public function getDefinitionOfField();
}
