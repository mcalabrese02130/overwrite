<?php

namespace Drupal\overwrite;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *  Manipulates entity type information.
 *
 *  This class contains primarily bridged hooks for compile-time or
 *  cache-clear-time hooks. Runtime hooks should be placed in
 *  EntityOperations.
 **/
class EntityTypeInfo implements ContainerInjectionInterface {

  use StringTranslationTrait;

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user')
    );
  }



  /**
   * Adds devel links to appropriate entity types.
   *
   * This is an alter hook bridge.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface[] $entity_types
   * The master entity type list to alter.
   *
   * @see hook_entity_type_alter()
   **/
  public function entityTypeAlter(array &$entity_types) {
    foreach ($entity_types as $entity_type_id => $entity_type) {
      if ($entity_type->getFormClass('edit') && $entity_type->hasLinkTemplate('edit-form') && $entity_type->hasLinkTemplate('canonical')) {
        $entity_type->setLinkTemplate('overwrite', '/overwrite/' . $entity_type_id . '/{' . $entity_type_id . '}');
      }
    }
  }

  public function entityOperation(EntityInterface $entity) {
    if($entity->hasLinkTemplate('overwrite')) {
      return array(
        'overwrite' => array(
          'title' => $this->t('Overwrite'),
          'weight' => 0,
          'url' => $entity->toUrl('overwrite'),
        )
      );
    }
    return array();
  }
}
