<?php

namespace Drupal\ai_readme_generator\AI;

use GuzzleHttp\Client;

/**
 * Handles communication with the AI service to generate README.md content.
 *
 * This class connects to an external AI API using Guzzle to generate
 * documentation based on parsed Drupal module metadata.
 */
class AIResponse {

  /**
   * The Guzzle HTTP client instance.
   *
   * @var \GuzzleHttp\Client
   */
  protected Client $client;

  /**
   * Configuration array containing API credentials and settings.
   *
   * @var array
   */
  protected array $config;

  /**
   * Constructs an AIResponse object.
   *
   * @param array $config
   *   An array containing:
   *   - base_uri: The base URI of the AI API.
   *   - api_key: The API key for authentication.
   *   - chat_endpoint: The endpoint path for chat completion.
   *   - model: The model identifier to use.
   */
  public function __construct(array $config) {
    $this->config = $config;

    $this->client = new Client([
      'base_uri' => $this->config['base_uri'],
      'headers' => [
        'Authorization' => 'Bearer ' . $this->config['api_key'],
        'Content-Type' => 'application/json',
      ],
    ]);
  }

  /**
   * Generates a summarized README.md file based on parsed module data.
   *
   * @param array $moduleData
   *   An associative array of extracted module information.
   *
   * @return string
   *   The generated README content or an error message.
   */
  public function summarizeArray(array $moduleData): string {
    $jsonContent = json_encode($moduleData, JSON_PRETTY_PRINT);

    // Prompt for Generating README.md.
    $template = <<<EOT
  You are a Drupal module documentation expert. Your task is to generate only the contents of a README.md file for a Drupal module.
  
  IMPORTANT:
  - Do NOT start with lines like "Here is the README for..."
  - The output should START directly after your response begins.
  - Do NOT include the "CONTENTS OF THIS FILE" section — it will be added manually.
  - Follow the format for sections like Introduction, Requirements, etc.
  - After headings of section leave one line.
  # [Module Name]
  
  ## Introduction
  Write a detailed explanation of what the module does in 4 to 5 lines and in more detail you are not giving in detail.
  
  ## Requirements
  Only list the names of required modules or Drupal core. Do not explain them, and start each module name with a capital letter if no requirement write none.
  
  ## Installation
  Only write the composer command without backticks:
  composer require drupal/module_machine_name
  
  ## Recommended modules
  List names of recommended modules don't give . No descriptions.
  
  ## Configuration
  Explain in detail and in points how to configure the module after enabling it.
  
  ## Maintainers
  Add a placeholder for the maintainer.
  
  Analyze the following Drupal module data and generate the content accordingly:
  
  {$jsonContent}
  EOT;

    $maxTokens = 1000;

    try {
      $response = $this->client->post($this->config['chat_endpoint'], [
        'json' => [
          'model' => $this->config['model'],
          'messages' => [
            [
              'role' => 'user',
              'content' => $template,
            ],
          ],
          'max_tokens' => $maxTokens,
        ],
      ]);

      $body = json_decode($response->getBody(), TRUE);
      $readmeBody = $body['choices'][0]['message']['content'] ?? 'No README generated.';

      $contentsHeader = <<<HEADER
  CONTENTS OF THIS FILE
  
  - Introduction
  - Requirements
  - Installation
  - Recommended modules
  - Configuration
  - Maintainers
  
  HEADER;

      return trim($contentsHeader . "\n" . ltrim($readmeBody));
    }
    catch (\Exception $e) {
      return 'Error: ' . $e->getMessage();
    }
  }

}
