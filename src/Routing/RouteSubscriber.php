<?php

namespace Drupal\overwrite\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for Overwrite routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager) {
    $this->entityTypeManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($route = $this->getEntityOverwrite($entity_type)) {
        $collection->add("entity.$entity_type_id.overwrite", $route);
      }
    }
  }

  /**
   * Gets the entity load route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getEntityOverwrite(EntityTypeInterface $entity_type) {
    if ($overwrite = $entity_type->getLinkTemplate('overwrite')) {
      $entity_type_id = $entity_type->id();
      $route = new Route($overwrite);
      $route
        ->addDefaults([
          '_controller' => '\Drupal\overwrite\Controller\OverwriteController::overwriteDefinition',
          '_title' => 'Overwrite',
        ])
        ->addRequirements([
          '_permission' => 'administer site content',
        ])
        ->setOption('_admin_route', TRUE)
        ->setOption('_overwrite_entity_type_id', $entity_type_id)
        ->setOption('parameters', [
          $entity_type_id  => ['type' => 'entity:' . $entity_type_id],
        ]);

      return $route;
    }
  }

}
