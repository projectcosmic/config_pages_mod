<?php

namespace Drupal\config_pages_mod\Plugin\views\area;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\area\Entity;

/**
 * Renders a config page entity in a certain view mode.
 *
 * Removes UUIDs from any exported config and uses the config page entity bundle
 * as the dependency since config page entities are singletons.
 *
 * @ViewsArea("config_pages_mod_config_page")
 */
class ConfigPage extends Entity {

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['target']['#title'] = $this->t('Config page');
    $form['target']['#default_value'] = $this->options['target'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    if (!$empty || !empty($this->options['empty'])) {
      $entity = $this->entityTypeManager
        ->getStorage($this->entityType)
        ->load((string) $this->tokenizeValue($this->options['target']));

      if ($entity && (!empty($this->options['bypass_access']) || $entity->access('view'))) {
        return $this->entityTypeManager
          ->getViewBuilder($this->entityType)
          ->view($entity, $this->options['view_mode']);
      }
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();

    // Ensure that we don't add dependencies for placeholders.
    // @todo Use a method to check for tokens in
    //   https://www.drupal.org/node/2396607.
    if (strpos($this->options['target'], '{{') === FALSE) {
      if ($entity = $this->entityTypeManager->getStorage('config_pages_type')->load($this->options['target'])) {
        $dependencies[$entity->getConfigDependencyKey()][] = $entity->getConfigDependencyName();
      }
    }

    return $dependencies;
  }

}
