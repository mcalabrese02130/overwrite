<?php

namespace Drupal\overwrite\Entity;

use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Overwrite entity.
 *
 * @ingroup overwrite
 *
 * @ContentEntityType(
 *   id = "overwrite",
 *   label = @Translation("Overwrite"),
 *   admin_permission = "administer site configuration",
 *   base_table = "overwrite",
 *   fieldable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *   },
 * )
 */
class Overwrite extends ContentEntityBase implements OverwriteInterface {
  /**
   * {@inheritdoc}
   */
  public function getRelatedEntityType() {
    return $this->get('related_entity_type')->value;
  }

  /**
   * {@inheritdoc}
   */ 
  public function getRelatedEntityId() {
    return $this->get('related_entity_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setFieldValue($value) {
    $this->set('value', $value);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldValue() {
    return $this->get('value');
  }

  /**
   * {@inheritdoc}
   */ 
  public function getMethod() {
    return $this->get('method')->value;
  }

  /**
   * {@inheritdoc}
   */ 
  public function setMethod($method) {
    $this->set('method', $method);
  }

  /**
   * {@inheritdoc}
   */
  public function getRelatedFieldname() {
    return $this->get('related_fieldname')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitionOfField() {
    $entity = entity_load($this->getRelatedEntityType(), $this->getRelatedEntityId());
    $bundle_fields = $this->entityManager()->getFieldDefinitions($this->getRelatedEntityType(), $entity->bundle());
    return $bundle_fields[$this->getRelatedFieldname()];
  }
  
  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Overwrite ID'))
      ->setReadOnly(TRUE);

    $fields['related_entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity Type'));

    $fields['related_entity_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Entity ID'));

    $fields['related_fieldname'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Fieldname'));

    $fields['method'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Method'));

    $fields['value'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Field value'));

    return $fields;
  }
}
