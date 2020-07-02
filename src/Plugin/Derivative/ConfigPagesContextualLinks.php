<?php

namespace Drupal\config_pages_mod\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic contextual links for config pages.
 */
class ConfigPagesContextualLinks extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The config pages type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $configPagesTypeStorage;

  /**
   * Constructs a new ConfigPagesContextualLinks.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation) {
    $this->configPagesTypeStorage = $entity_type_manager->hasHandler('config_pages_type', 'storage')
      ? $entity_type_manager->getStorage('config_pages_type')
      : NULL;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    if (isset($this->configPagesTypeStorage)) {
      foreach ($this->configPagesTypeStorage->loadMultiple() as $page) {
        $route_name = "config_pages.{$page->id()}";
        $this->derivatives[$route_name]['route_name'] = $route_name;
        $this->derivatives[$route_name]['group'] = 'config_pages';
        $this->derivatives[$route_name]['title'] = $this->t('Edit in @title', ['@title' => $page->label()]);
      }
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
