<?php

namespace Drupal\overwrite\Form;

use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\overwrite\Controller\OverwriteController;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\overwrite\Entity\Overwrite;
use Drupal\Core\Cache\Cache;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 */
class OverwriteForm extends FormBase {

  private $entityTypeManager;
  private $entityFieldManager;
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'overwrite_form';
  }

  /**
   * Class constructor.
   *
   * @param EntityTypeManager $entityTypeManager
   * @param EntityFieldManager $entityFieldManager
   */
  public function __construct(EntityTypeManager $entityTypeManager, EntityFieldManager $entityFieldManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, EntityInterface $entity = NULL) {
    if ($entity == NULL) {
      return $form;
    }

    $entity_type = $entity->getEntityTypeId();
    $entity_id = $entity->id();

    $form['entity_type'] = [
      '#type' => 'value',
      '#value' => $entity_type,
    ];

    $form['entity_id'] = [
      '#type' => 'value',
      '#value' => $entity_id,
    ];

    // Get an array of fields associated with the entity bundle
    $field_options = [];
    $bundle_fields = $this->entityFieldManager->getFieldDefinitions($entity_type, $entity->bundle());
    $label_fieldname = $this->entityTypeManager->getDefinition($entity_type)->getKey('label');
    foreach ($bundle_fields as $fieldname => $field) {
      if (
        get_class($field) == 'Drupal\field\Entity\FieldConfig' ||
        $fieldname == $label_fieldname
      ) {
        $field_options[$fieldname] = $field->getLabel();
      }
    }

    $form['field_select'] = [
      '#type' => 'select',
      '#options' => $field_options,
      '#title' => $this->t('Field'),
    ];
    $form['field_add'] = [
      '#type' => 'button',
      '#value' => $this->t('Add Field'),
      '#name' => 'field_add',
      '#ajax' => [
        'method' => 'prepend',
        'callback' => '::addFieldCallback',
        'wrapper' => 'field-overwrites',
      ],
    ];

    $form['field_overwrites'] = [
      '#tree' => TRUE,
      '#parents' => ['field_overwrites'],
      '#type' => 'fieldset',
      '#responsive' => FALSE,
      '#header' => [
        'field' => $this->t('Field'),
        'method' => $this->t('Method'),
        'remove' => $this->t('Action'),
      ],
    ];

    $trigger = $form_state->getTriggeringElement();
    if (substr($trigger['#name'], 0, 17) == 'remove-overwrite-') {
      $overwrite = Overwrite::load($trigger['#overwrite_id']);
      try {
        $overwrite->delete();
      }
      catch (EntityStorageException $e) {
        \Drupal::logger('overwrite')->error($e->getMessage());
      }

    }

    $overwrites = OverwriteController::getOverwrites($entity_type, $entity_id);
    foreach ($overwrites as $overwrite) {
      $this->addOverwrite($form['field_overwrites'], $form_state, $overwrite);
    }

    if ($trigger['#name'] == 'field_add') {
      $values = $form_state->getValues();
      $overwrite = Overwrite::create([
        'related_entity_type' => $values['entity_type'],

        'related_entity_id' => (integer) $values['entity_id'],

        'related_fieldname' => $values['field_select'],
      ]);
      try {
        $overwrite->save();
      }
      catch(EntityStorageException $e) {
        \Drupal::logger('overwrite')->error($e->getMessage());
      }

      $form['overwrite_added'] = [
        '#type' => 'value',
        '#value' => $overwrite->id(),
      ];
      $this->addOverwrite($form['field_overwrites'], $form_state, $overwrite);
    }

    $form['field_overwrites'][0] = [
      'empty' => [
        '#markup' => '<div id="field-overwrites"></div>',
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * Helper function adds the field widget for the passed $overwrite to the $form array.
   *
   * @param array $form
   *   Form array passed by reference.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   FormStateInterface passed by reference.
   * @param \Drupal\overwrite\Entity\Overwrite $overwrite
   *   Overwrite entity to be added.
   */
  private function addOverwrite(array &$form, FormStateInterface &$form_state, Overwrite $overwrite) {
    $overwrite_parents = $form['#parents'];
    $overwrite_parents[] = $overwrite->id();
    $field_parents = $overwrite_parents;
    $field_parents[] = 'field';
    $form[$overwrite->id()] = [
      '#prefix' => '<div id="overwrite-' . $overwrite->id() . '">',
      '#suffix' => '</div>',
      '#parents' => $overwrite_parents,
    ];

    // Get the entity related to $overwrite.
    $entity = $this->entityTypeManager
      ->getStorage($overwrite->getRelatedEntityType())
      ->load($overwrite->getRelatedEntityId());

    // Get form associated with the entity
    $entity_form = $this->entityTypeManager
      ->getStorage('entity_form_display')
      ->load($overwrite->getRelatedEntityType() . '.' . $entity->bundle() . '.default');

    // Get renderer for the field
    $field_renderer = $entity_form->getRenderer($overwrite->getRelatedFieldname());

    if ($field_renderer) {
      $field = $overwrite->getDefinitionOfField();
      if (!$field) {
        return;
      }

      // Create FieldItemList with data from $overwrite.
      if ($field->getType() == 'entity_reference') {
        // Entity reference fields are a special case for how the data is stored.
        $field_item_list = EntityReferenceFieldItemList::createInstance($field, 'overwrite_fields[' . $overwrite->id() . ']', $entity->getTypedData());
        $overwrite_field_values = $overwrite->getFieldValue()->getValue();
        $field_item_list->setValue($overwrite_field_values);
      }
      else {
        $field_item_list = FieldItemList::createInstance($field, 'overwrite_fields[' . $overwrite->id() . ']', $entity->getTypedData());
        $overwrite_field_item_list = $overwrite->getFieldValue();
        $field_item_list->setValue($overwrite_field_item_list->getValue());
      }
      //Render the field widget.
      $form[$overwrite->id()]['field'] = $field_renderer->form($field_item_list, $form[$overwrite->id()], $form_state);

      $form[$overwrite->id()]['method'] = [
        '#title' => $this->t('Method'),
        '#type' => 'select',
        '#options' => [
          'replace' => $this->t('Replace'),
          'prepend' => $this->t('Prepend'),
          'append' => $this->t('Append'),
        ],
        '#default_value' => $overwrite->getMethod(),
      ];
      $form[$overwrite->id()]['remove'] = [
        '#type' => 'button',
        '#value' => $this->t('Remove'),
        '#name' => 'remove-overwrite-' . $overwrite->id(),
        '#overwrite_id' => $overwrite->id(),
        '#ajax' => [
          'wrapper' => 'overwrite-' . $overwrite->id(),
          'callback' => '::removeOverwriteCallback',
          'method' => 'replace',
        ],
      ];
      $form[$overwrite->id()]['field_type'] = [
        '#type' => 'value',
        '#value' => $field ? $field->getType() : NULL,
      ];

      $form[$overwrite->id()]['fieldname'] = [
        '#type' => 'value',
        '#value' => $overwrite->getRelatedFieldname(),
      ];
    }
  }

  /**
   * Callback function to remove an overwrite.
   */
  public function removeOverwriteCallback(array $form, FormStateInterface $form_state) {
    return [
      '#markup' => '',
    ];
  }

  /**
   * Callback function to add an overwrite.
   */
  public function addFieldCallback(array $form, FormStateInterface $form_state) {
    return $form['field_overwrites'][$form_state->getValue('overwrite_added')];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    foreach ($values['field_overwrites'] as $overwrite_id => $overwrite_data) {
      $overwrite = Overwrite::load($overwrite_id);
      $overwrite->setMethod($overwrite_data['method']);

      $fieldname = $overwrite_data['fieldname'];
      if ($fieldname) {
        $data = $overwrite_data[$fieldname];
        switch ($overwrite_data['field_type']) {
          case 'entity_reference':
            if (isset($data['target_id'])) {
              $data = $data['target_id'];
            }
            break;

          case 'image':
            foreach ($data as &$data_entry) {
              if (isset($data_entry['fids'][0])) {
                $data_entry['target_id'] = $data_entry['fids'][0];
              }
            }
            break;
        }
        $overwrite->setFieldValue($data);
      }
      try {
        $overwrite->save();
      }
      catch (EntityStorageException $e) {
        \Drupal::logger('overwrite')->error($e->getMessage());
      }
    }
    Cache::invalidateTags([$values['entity_type'] . ':' . $values['entity_id']]);
  }

}
