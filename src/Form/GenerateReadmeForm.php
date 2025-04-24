<?php

namespace Drupal\ai_readme_generator\Form;

use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\Core\Extension\InfoParserInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\ai_readme_generator\Scanner\CodebaseScanner;
use Drupal\ai_readme_generator\AI\AIResponse;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Extension\ModuleExtensionList;

/**
 * Class GenerateReadmeForm.
 *
 * Provides a form to select a contrib module and generate a README.md for it.
 */
class GenerateReadmeForm extends FormBase {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module extension list service.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The info parser service.
   *
   * @var \Drupal\Core\Extension\InfoParserInterface
   */
  protected $infoParser;

  /**
   * Constructor.
   */
  public function __construct(
    ModuleHandlerInterface $module_handler,
    ConfigFactoryInterface $config_factory,
    ModuleExtensionList $module_extension_list,
    MessengerInterface $messenger,
    InfoParserInterface $info_parser,
  ) {
    $this->moduleHandler = $module_handler;
    $this->configFactory = $config_factory;
    $this->moduleExtensionList = $module_extension_list;
    $this->messenger = $messenger;
    $this->infoParser = $info_parser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('config.factory'),
      $container->get('extension.list.module'),
      $container->get('messenger'),
      $container->get('info_parser')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'generate_readme_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['module_name'] = [
      '#type' => 'select',
      '#title' => $this->t('Select a module'),
      '#options' => $this->getTopLevelCustomAndContribModules(),
      '#required' => TRUE,
    ];

    $form['generate_readme'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate README.md'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $module_name = $form_state->getValue('module_name');
    $discovery = new ExtensionDiscovery(DRUPAL_ROOT);
    $all_modules = $discovery->scan('module');

    if (!isset($all_modules[$module_name])) {
      $this->messenger->addError(
        $this->t('Selected module does not exist.')
      );
      return;
    }

    $extension = $all_modules[$module_name];
    $module_path = DRUPAL_ROOT . '/' . $extension->getPath();

    $scanner = new CodebaseScanner($module_path);
    $moduleData = $scanner->scan();

    $config = $this->configFactory
      ->get('ai_readme_generator.settings')
      ->get();

    $ai = new AIResponse($config);
    $summary = $ai->summarizeArray($moduleData);

    $readme_path = $module_path . '/README.md';
    file_put_contents($readme_path, $summary);

    $this->messenger->addMessage(
      $this->t('README.md generated successfully at: @path', [
        '@path' => $readme_path,
      ])
    );
  }

  /**
   * Get modules.
   *
   * @return array
   *   An associative array of machine_name => module name.
   */
  public function getTopLevelCustomAndContribModules(): array {
    $modules = [];
    $discovery = new ExtensionDiscovery(DRUPAL_ROOT);
    $all_modules = $discovery->scan('module');

    foreach ($all_modules as $machine_name => $extension) {
      $path = $extension->getPath();

      if (str_starts_with($path, 'core/modules')) {
        continue;
      }

      if (
        (str_starts_with($path, 'modules/custom') ||
        str_starts_with($path, 'modules/contrib')) &&
        substr_count($path, '/') > 2
      ) {
        continue;
      }

      $info = $this->infoParser->parse($extension->getPathname());
      $modules[$machine_name] = $info['name'] ?? $machine_name;
    }

    asort($modules);
    return $modules;
  }

}
