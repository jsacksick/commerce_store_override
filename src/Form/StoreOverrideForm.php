<?php

namespace Drupal\commerce_store_override\Form;

use Drupal\commerce_store_override\StoreOverride;
use Drupal\commerce_store_override\StoreOverrideRepositoryInterface;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class StoreOverrideForm extends EntityForm {

  /**
   * The store override repository.
   *
   * @var \Drupal\commerce_store_override\StoreOverrideRepositoryInterface
   */
  protected $repository;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The store.
   *
   * @var \Drupal\commerce_store\Entity\StoreInterface
   */
  protected $store;

  /**
   * The entity.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $entity;

  /**
   * The store override.
   *
   * @var \Drupal\commerce_store_override\StoreOverride
   */
  protected $storeOverride;

  /**
   * Constructs a new StoreOverrideForm object.
   *
   * @param \Drupal\commerce_store_override\StoreOverrideRepositoryInterface $repository
   *   The store override repository.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   */
  public function __construct(StoreOverrideRepositoryInterface $repository, RouteMatchInterface $route_match) {
    $this->repository = $repository;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_store_override.repository'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function init(FormStateInterface $form_state) {
    $this->store = $this->routeMatch->getParameter('commerce_store');
    $this->storeOverride = $this->repository->load($this->store, $this->entity);
    if ($this->storeOverride) {
      $this->storeOverride->apply($this->entity);
    }

    $form_display = EntityFormDisplay::collectRenderDisplay($this->entity, 'edit');
    // Hide fields that shouldn't be overridden.
    // @todo Make this configurable per bundle.
    $whitelist = ['title', 'sku', 'price', 'field_product_categories', 'field_address'];
    foreach ($form_display->getComponents() as $name => $component) {
      if (!in_array($name, $whitelist)) {
        $form_display->removeComponent($name);
      }
    }
    $this->setFormDisplay($form_display, $form_state);

    parent::init($form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    // Content entity forms do not use the parent's #after_build callback
    // because they only need to rebuild the entity in the validation and the
    // submit handler because Field API uses its own #after_build callback for
    // its widgets.
    unset($form['#after_build']);

    $form['data'] = [
      '#type' => 'container',
      '#parents' => ['data'],
    ];
    $form_display = $this->getFormDisplay($form_state);
    $form_display->buildForm($this->entity, $form['data'], $form_state);

    $form['footer'] = [
      '#type' => 'container',
      '#weight' => 99,
      '#attributes' => [
        'class' => ['entity-content-form-footer'],
      ],
    ];
    $form['footer']['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Active'),
      '#default_value' => $this->storeOverride ? $this->storeOverride->getStatus() : TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#submit' => ['::submitForm', '::save'],
    ];

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $entity = clone $this->entity;
    $form_display = $this->getFormDisplay($form_state);
    $form_display->extractFormValues($entity, $form['data'], $form_state);
    $form_display->validateFormValues($entity, $form['data'], $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = clone $this->entity;
    $form_display = $this->getFormDisplay($form_state);
    $extracted = $form_display->extractFormValues($entity, $form['data'], $form_state);
    $data = [];
    foreach ($extracted as $field_name) {
      $data[$field_name] = $entity->get($field_name)->getValue();
      // Remove empty properties, to reduce the size of the stored value.
      foreach ($data[$field_name] as $delta => $value) {
        $data[$field_name][$delta] = array_filter($value, 'strlen');
      }
      // Unwrap the list if it only has a single delta.
      $keys = array_keys($data[$field_name]);
      if (count($keys) === 1 && $keys[0] === 0) {
        $data[$field_name] = reset($data[$field_name]);
      }
    }

    $this->storeOverride = StoreOverride::create($this->store, $this->entity, [
      'data' => $data,
      'status' => $form_state->getValue('status'),
    ]);
    // The entity label is used in save(), update it with the new override data.
    $this->storeOverride->apply($this->entity);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->repository->save($this->storeOverride);
    $this->messenger()->addStatus($this->t('Saved %label.', [
      '%label' => $this->entity->label(),
    ]));
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    // There is no need to modify the entity.
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFromRouteMatch(RouteMatchInterface $route_match, $entity_type_id) {
    $entity_type_id = $this->routeMatch->getParameter('entity_type_id');
    $entity = $this->routeMatch->getParameter($entity_type_id);

    return $entity;
  }

  /**
   * Gets the form display used to build the override form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Entity\Display\EntityFormDisplayInterface
   *   The form display.
   */
  protected function getFormDisplay(FormStateInterface $form_state) {
    return $form_state->get('form_display');
  }

  /**
   * Sets the form display used to build the override form.
   *
   * @param \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display
   *   The form display.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return $this
   */
  protected function setFormDisplay(EntityFormDisplayInterface $form_display, FormStateInterface $form_state) {
    $form_state->set('form_display', $form_display);
    return $this;
  }

}
