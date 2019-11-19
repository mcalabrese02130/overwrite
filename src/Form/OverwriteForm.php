<?php

namespace Drupal\overwrite\Form;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\overwrite\Controller\OverwriteController;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\overwrite\Entity\Overwrite;
use Drupal\Core\Cache\Cache;

/**
 *
 */
class OverwriteForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'overwrite_form';
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

    //Get an array of fields associated with the entity bundle
    $bundle_fields = \Drupal::entityManager()->getFieldDefinitions($entity_type, $entity->bundle());
    $field_options = [];
    $label_fieldname = \Drupal::entityTypeManager()->getDefinition($entity_type)->getKey('label');
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
      $overwrite->delete();
    }

    $overwrites = OverwriteController::getOverwrites($entity_type, $entity_id);
    foreach ($overwrites as $overwrite) {
      $this->addOverwrite($form['field_overwrites'], $form_state, $overwrite);
    }

    if ($trigger['#name'] == 'field_add') {
      $values = $form_state->getValues();
      $overwrite = entity_create('overwrite', [
        'related_entity_type' => $values['entity_type'],

        'related_entity_id' => (integer) $values['entity_id'],

        'related_fieldname' => $values['field_select'],
      ]);
      $overwrite->save();
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

    //Get the entity related to $overwrite.
    $entity = entity_load($overwrite->getRelatedEntityType(), $overwrite->getRelatedEntityId());
    $entity_form = \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load($overwrite->getRelatedEntityType() . '.' . $entity->bundle() . '.default');

    $field_renderer = $entity_form->getRenderer($overwrite->getRelatedFieldname());
    if ($field_renderer) {
      $empty_entity = entity_create($overwrite->getRelatedEntityType(), ['type' => $entity->bundle()]);

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
      $overwrite->save();
    }
    Cache::invalidateTags([$values['entity_type'] . ':' . $values['entity_id']]);
  }

}
