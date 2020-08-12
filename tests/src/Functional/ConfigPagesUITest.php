<?php

namespace Drupal\Tests\config_pages_mod\Functional;

use Drupal\config_pages\Entity\ConfigPagesType;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests UI improvements to config pages.
 *
 * @group config_pages_mod
 * @requires module config_pages
 */
class ConfigPagesUITest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['config_pages', 'config_pages_mod'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Config page entity bundle used in tests.
   *
   * @var \Drupal\config_pages\ConfigPagesTypeInterface
   */
  protected $configBundle;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->configBundle = ConfigPagesType::create([
      'id' => mb_strtolower($this->randomMachineName()),
      'label' => $this->randomMachineName(),
      'context' => ['show_warning' => FALSE],
      'menu' => [],
    ]);
    $this->configBundle->save();
    \Drupal::service('router.builder')->rebuild();

    $this->drupalLogin($this->drupalCreateUser([], NULL, TRUE));
  }

  /**
   * Tests alterations to the config pages entity forms.
   *
   * @see dctogether_helper_config_pages_form_submit()
   * @see dctogether_helper_form_config_pages_form_alter()
   */
  public function testConfigPagesForm() {
    $label = $this->configBundle->label();

    $this->drupalGet(Url::fromRoute('config_pages.' . $this->configBundle->id()));

    $assert = $this->assertSession();
    $assert->titleEquals("$label | Drupal");
    $assert->buttonExists('Save')->press();

    $this->assertRaw(t('@info updated.', ['@info' => $label]), 'Ensure rewritten success message is shown.');
    $this->assertNoRaw(t('@type %info has been created.', ['@type' => $label, '%info' => $label]), 'Ensure original success message is not shown.');
    $this->assertNoRaw(t('@type %info has been updated.', ['@type' => $label, '%info' => $label]), 'Ensure original success message is not shown.');
  }

}
