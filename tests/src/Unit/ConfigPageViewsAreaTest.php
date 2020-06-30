<?php

namespace Drupal\Tests\config_pages_mod\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\config_pages_mod\Plugin\views\area\ConfigPage;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\config_pages_mod\Plugin\views\area\ConfigPage
 * @group config_pages_mod
 */
class ConfigPageViewsAreaTest extends UnitTestCase {

  /**
   * The tested entity area handler.
   *
   * @var \Drupal\views\Plugin\views\area\Entity
   */
  protected $entityHandler;

  /**
   * The mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityRepository;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityDisplayRepository;

  /**
   * The mocked entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityStorage;

  /**
   * The mocked entity storage for config pages types.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityStorageType;

  /**
   * The mocked entity view builder.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityViewBuilder;

  /**
   * The mocked view executable.
   *
   * @var \Drupal\views\ViewExecutable|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $executable;

  /**
   * The mocked display.
   *
   * @var \Drupal\views\Plugin\views\display\DisplayPluginBase|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $display;

  /**
   * The mocked style plugin.
   *
   * @var \Drupal\views\Plugin\views\style\StylePluginBase|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $stylePlugin;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->entityTypeManager = $this->createMock('Drupal\Core\Entity\EntityTypeManagerInterface');
    $this->entityRepository = $this->createMock('Drupal\Core\Entity\EntityRepositoryInterface');
    $this->entityDisplayRepository = $this->createMock('Drupal\Core\Entity\EntityDisplayRepositoryInterface');
    $this->entityStorage = $this->createMock('Drupal\Core\Entity\EntityStorageInterface');
    $this->entityStorageType = $this->createMock('Drupal\Core\Entity\EntityStorageInterface');
    $this->entityViewBuilder = $this->createMock('Drupal\Core\Entity\EntityViewBuilderInterface');
    $this->entitySelectionManager = $this->createMock('Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface');

    $this->executable = $this->getMockBuilder('Drupal\views\ViewExecutable')
      ->disableOriginalConstructor()
      ->getMock();
    $this->display = $this->getMockBuilder('Drupal\views\Plugin\views\display\DisplayPluginBase')
      ->disableOriginalConstructor()
      ->getMock();
    $this->stylePlugin = $this->getMockBuilder('Drupal\views\Plugin\views\style\StylePluginBase')
      ->disableOriginalConstructor()
      ->getMock();
    $this->executable->style_plugin = $this->stylePlugin;

    $this->entityHandler = new ConfigPage([], 'entity', ['entity_type' => 'config_pages'], $this->entityTypeManager, $this->entityRepository, $this->entityDisplayRepository);

    $this->display->expects($this->any())
      ->method('getPlugin')
      ->with('style')
      ->willReturn($this->stylePlugin);
    $this->executable->expects($this->any())
      ->method('getStyle')
      ->willReturn($this->stylePlugin);

    $token = $this->getMockBuilder('Drupal\Core\Utility\Token')
      ->disableOriginalConstructor()
      ->getMock();
    $token->expects($this->any())
      ->method('replace')
      ->willReturnArgument(0);

    $container = new ContainerBuilder();
    $container->set('token', $token);
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::render
   */
  public function testRender() {
    $options = [
      'target' => 'foo',
      'tokenize' => FALSE,
    ];

    /** @var \Drupal\Core\Entity\ContentEntityInterface $config_page */
    $config_page = $this->createMock('Drupal\Core\Entity\ContentEntityInterface');
    $config_page->expects($this->once())->method('access')->willReturn(TRUE);

    $this->entityRepository->expects($this->never())->method('loadEntityByConfigTarget');

    $this->entityStorage
      ->expects($this->once())
      ->method('load')
      ->with('foo')
      ->willreturn($config_page);

    $this->entityTypeManager
      ->expects($this->once())
      ->method('getStorage')
      ->with('config_pages')
      ->willReturn($this->entityStorage);

    $this->entityViewBuilder
      ->expects($this->once())
      ->method('view')
      ->with($config_page, 'default')
      ->willReturn(['#markup' => 'hallo']);

    $this->entityTypeManager
      ->expects($this->any())
      ->method('getViewBuilder')
      ->with('config_pages')
      ->willReturn($this->entityViewBuilder);

    $this->entityHandler->init($this->executable, $this->display, $options);
    $result = $this->entityHandler->render();
    $this->assertEquals(['#markup' => 'hallo'], $result);
  }

  /**
   * @covers ::calculateDependencies
   */
  public function testCalculateDependencies() {
    $options = ['target' => '{{ test_render_token }}'];
    $this->entityHandler->init($this->executable, $this->display, $options);
    $this->assertEquals([], $this->entityHandler->calculateDependencies(), 'No dependency entry for target with tokens.');

    $config_pages_type = $this->createMock('Drupal\Core\Config\Entity\ConfigEntityInterface');
    $config_pages_type->method('getConfigDependencyKey')->willReturn('config');
    $config_pages_type->method('getConfigDependencyName')->willReturn('config_pages.type.foo');

    $this->entityRepository
      ->expects($this->once())
      ->method('loadEntityByConfigTarget')
      ->willReturn(NULL);

    $this->entityStorageType
      ->expects($this->once())
      ->method('load')
      ->with('foo')
      ->willreturn($config_pages_type);

    $this->entityTypeManager
      ->expects($this->once())
      ->method('getStorage')
      ->with('config_pages_type')
      ->willReturn($this->entityStorageType);

    $options = ['target' => 'foo'];
    $this->entityHandler->init($this->executable, $this->display, $options);
    $this->assertEqualsCanonicalizing(['config' => ['config_pages.type.foo']], $this->entityHandler->calculateDependencies());
  }

}
