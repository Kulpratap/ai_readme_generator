<?php

namespace Drupal\ai_readme_generator\Commands;

use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Innoraft\ReadmeGenerator\Scanner\CodebaseScanner;
use Innoraft\ReadmeGenerator\AI\AIResponse;

/**
 * Defines Drush commands for the AI Readme Generator module.
 */
final class ReadmeGeneratorCommands extends DrushCommands {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Constructs a new ReadmeGeneratorCommands object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory) {
    parent::__construct();
    $this->moduleHandler = $module_handler;
    $this->configFactory = $config_factory;
  }

  /**
   * Generate README.md file for a module using AI.
   *
   * @param string $module
   *   The machine name of the module.
   */
  #[CLI\Command(name: 'readme-generator', description: 'Generate README.md file for a module using AI')]
  #[CLI\Argument(name: 'module', description: 'The machine name of the module')]
  public function generate(string $module): void {
    $module_object = $this->moduleHandler->getModule($module);

    if (!$module_object) {
      $this->output()->writeln("❌ Module '$module' not found.");
      return;
    }

    $module_path = $module_object->getPath();
    $scanner = new CodebaseScanner($module_path);
    $moduleData = $scanner->scan();

    $config = $this->configFactory->get('ai_readme_generator.settings')->get();
    $ai = new AIResponse($config);
    $summary = $ai->summarizeArray($moduleData);

    $readme_path = $module_path . '/README.md';
    file_put_contents($readme_path, $summary);

    $this->output()->writeln("✅ README.md generated at: $readme_path");
  }

}
