<?php

namespace Drupal\overwrite\Controller;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Controller\ControllerBase;


class OverwriteController extends ControllerBase {

  /**
   *  Controls route of the "Overview" tab
   *
   *  @see Drupal\overwrite\Routing\RouteSubscriber.php
   *
   *  @return array
   *  drupal form render array
   **/
  public function overwriteDefinition(RouteMatchInterface $route_match) {
    $entity_type_id = $route_match->getRouteObject()->getOption('_overwrite_entity_type_id');
    $related_entity = $route_match->getParameter($entity_type_id);
    return \Drupal::formBuilder()->getForm(\Drupal\overwrite\Form\OverwriteForm::class, $related_entity);
  }

  /**
   *  Get Overwrites that are related to the given entity criteria
   *
   *  @param string $entity_type
   *  @param $entity_id
   *
   *  @return array
   *  array of \Drupal\overwrite\Entity\Overwrite
   **/
  public static function getOverwrites(string $entity_type, $entity_id) {
//    if(\Drupal::database()->schema()->tableExists('overwrite')) {
      $overwrite_ids =  \Drupal::entityQuery('overwrite')
        ->condition('related_entity_type', $entity_type)
        ->condition('related_entity_id', $entity_id)
        ->execute();
      if(!empty($overwrite_ids)) {
        return \Drupal\overwrite\Entity\Overwrite::loadMultiple($overwrite_ids);
      }
 //   }
    return [];
  }
 
}

