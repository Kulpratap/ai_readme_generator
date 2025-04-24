<?php

namespace Drupal\ai_readme_generator\Scanner;

use Symfony\Component\Yaml\Yaml;

/**
 * Scans a Drupal module directory and extracts relevant metadata.
 *
 * This includes module information, file listings, functions, classes,
 * submodules, and Drupal-specific elements like hooks and configuration files.
 */
class CodebaseScanner {

  /**
   * The absolute path to the Drupal module.
   *
   * @var string
   */
  private string $modulePath;

  /**
   * Constructs a CodebaseScanner object.
   *
   * @param string $modulePath
   *   The path to the Drupal module directory.
   */
  public function __construct(string $modulePath) {
    $this->modulePath = rtrim($modulePath, '/');
  }

  /**
   * Scans the module directory and returns structured metadata.
   *
   * @return array
   *   An array containing information like module metadata, functions,
   *   classes, hooks, controllers, forms, and submodules.
   */
  public function scan(): array {
    $moduleInfo = $this->extractModuleInfo();
    $files = $this->listRelevantFiles();
    $parsedFunctionsAndClasses = $this->parseCodeFiles($files);
    $parsedUsefulData = $this->extractUsefulData($files);
    $submodules = $this->listSubmodules();

    return array_merge(
      $moduleInfo,
      $parsedFunctionsAndClasses,
      $parsedUsefulData,
      ['submodules' => $submodules]
    );
  }

  /**
   * Extracts module metadata from the .info.yml file.
   *
   * @return array
   *   An array containing the module name, description, and dependencies.
   */
  private function extractModuleInfo(): array {
    $infoFile = glob($this->modulePath . '/*.info.yml');
    $info = [
      'name' => basename($this->modulePath),
      'description' => 'No description found.',
      'dependencies' => [],
    ];

    if (!empty($infoFile) && file_exists($infoFile[0])) {
      $moduleInfo = Yaml::parseFile($infoFile[0]);
      $info['name'] = $moduleInfo['name'] ?? basename($this->modulePath);
      $info['description'] = $moduleInfo['description'] ?? 'No description available.';
      $info['dependencies'] = $moduleInfo['dependencies'] ?? [];
    }

    return $info;
  }

  /**
   * Lists relevant module files for scanning.
   *
   * @return array
   *   An array of file paths to scan.
   */
  private function listRelevantFiles(): array {
    $files = [];

    $topLevelPatterns = [
      '*.info.yml',
      '*.module',
      '*.install',
      '*.routing.yml',
      '*.permissions.yml',
      '*.links.menu.yml',
      '*.links.task.yml',
      '*.schema.yml',
    ];

    foreach ($topLevelPatterns as $pattern) {
      foreach (glob($this->modulePath . '/' . $pattern) as $file) {
        $files[] = $file;
      }
    }

    $subDirs = [
      'src/Controller/*.php',
      'src/Form/*.php',
      'src/Plugin/*.php',
      'src/Entity/*.php',
      'src/Utility/*.php',
      'config/install/*.yml',
    ];

    foreach ($subDirs as $pattern) {
      foreach (glob($this->modulePath . '/' . $pattern) as $file) {
        $files[] = $file;
      }
    }

    return $files;
  }

  /**
   * Lists any submodules found within the main module directory.
   *
   * @return array
   *   An array of submodule names and their descriptions.
   */
  private function listSubmodules(): array {
    $submodules = [];

    foreach (glob($this->modulePath . '/modules/*/*.info.yml') as $infoFile) {
      $machineName = basename($infoFile, '.info.yml');
      $infoData = Yaml::parseFile($infoFile);

      $submodules[] = [
        'name' => $machineName,
        'description' => $infoData['description'] ?? 'No description available.',
      ];
    }

    return $submodules;
  }

  /**
   * Parses PHP files to extract function and class declarations.
   *
   * @param array $files
   *   An array of files to scan.
   *
   * @return array
   *   An array containing discovered classes and functions.
   */
  private function parseCodeFiles(array $files): array {
    $functions = [];
    $classes = [];

    foreach ($files as $file) {
      $relativePath = str_replace($this->modulePath . '/', '', $file);
      $code = file_get_contents($file);

      if (preg_match_all('/function\s+(\w+)\s*\(/', $code, $matches)) {
        foreach ($matches[1] as $function) {
          $functions[] = "$relativePath::$function";
        }
      }

      if (preg_match_all('/class\s+(\w+)/', $code, $matches)) {
        foreach ($matches[1] as $class) {
          $classes[] = "$relativePath::$class";
        }
      }
    }

    return [
      'classes' => $classes,
      'functions' => $functions,
    ];
  }

  /**
   * Extracts useful Drupal-specific structures from the codebase.
   *
   * @param array $files
   *   An array of files to analyze.
   *
   * @return array
   *   An array containing hooks, controller classes, and form classes.
   */
  private function extractUsefulData(array $files): array {
    $hooks = [];
    $controllers = [];
    $forms = [];

    foreach ($files as $file) {
      $relativePath = str_replace($this->modulePath . '/', '', $file);
      $code = file_get_contents($file);

      if (preg_match_all('/function\s+(hook_[a-zA-Z_]+)\s*\(/', $code, $matches)) {
        foreach ($matches[1] as $hook) {
          $hooks[] = "$relativePath::$hook";
        }
      }

      if (strpos($relativePath, 'src/Controller/') !== FALSE) {
        if (preg_match('/class\s+(\w+)/', $code, $match)) {
          $controllers[] = "$relativePath::{$match[1]}";
        }
      }

      if (strpos($relativePath, 'src/Form/') !== FALSE) {
        if (preg_match('/class\s+(\w+)/', $code, $match)) {
          $forms[] = "$relativePath::{$match[1]}";
        }
      }
    }

    return [
      'hooks' => $hooks,
      'controllers' => $controllers,
      'forms' => $forms,
    ];
  }

}
