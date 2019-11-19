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

}

