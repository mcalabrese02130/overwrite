<?php

namespace Drupal\overwrite\Controller;

use Drupal\overwrite\Entity\Overwrite;
use Drupal\overwrite\Form\OverwriteForm;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Controller\ControllerBase;

/**
 *
 */
class OverwriteController extends ControllerBase {

  /**
   * Controls route of the "Overview" tab.
   *
   *  @see Drupal\overwrite\Routing\RouteSubscriber.php
   *
   * @param RouteMatchInterface $route_match
   *
   * @return array
   *   Drupal form render array
   */
  public function overwriteDefinition(RouteMatchInterface $route_match) {
    $entity_type_id = $route_match->getRouteObject()->getOption('_overwrite_entity_type_id');
    $related_entity = $route_match->getParameter($entity_type_id);
    return \Drupal::formBuilder()->getForm(OverwriteForm::class, $related_entity);
  }

  /**
   * Get Overwrites that are related to the given entity criteria.
   *
   * @param string $entity_type
   *   Entity type for the entity having the overwrite applied to.
   * @param int $entity_id
   *   Entity Id for the entity having the overwrite applied to.
   *
   * @return array
   *   array of \Drupal\overwrite\Entity\Overwrite
   */
  public static function getOverwrites(string $entity_type, $entity_id) {
    $overwrite_ids = \Drupal::entityQuery('overwrite')
      ->condition('related_entity_type', $entity_type)
      ->condition('related_entity_id', $entity_id)
      ->execute();
    if (!empty($overwrite_ids)) {
      return Overwrite::loadMultiple($overwrite_ids);
    }
    return [];
  }

}
