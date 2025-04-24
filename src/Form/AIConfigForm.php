<?php

namespace Drupal\ai_readme_generator\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a configuration form for AI settings.
 *
 * This form allows the user to configure the AI provider (Groq or OpenAI),
 * API key, and model selection for generating README files.
 */
class AIConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   *
   * Specifies which configuration settings this form will edit.
   */
  protected function getEditableConfigNames() {
    return ['ai_readme_generator.settings'];
  }

  /**
   * {@inheritdoc}
   *
   * Returns the unique form ID.
   */
  public function getFormId() {
    return 'ai_readme_generator_config_form';
  }

  /**
   * {@inheritdoc}
   *
   * Builds the form for the AI configuration.
   *
   * This includes fields for selecting the AI provider, entering the API key,
   * and choosing the model to be used by the selected provider.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ai_readme_generator.settings');
    $provider = $form_state->getValue('provider') ?? $config->get('provider');

    $providers = [
      'openai' => [
        'label' => 'OpenAI',
        'base_uri' => 'https://api.openai.com/v1/',
        'chat_endpoint' => 'chat/completions',
        'models' => [
          'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
          'gpt-4' => 'GPT-4',
        ],
        'groq' => [
          'label' => 'Groq',
          'base_uri' => 'https://api.groq.com/',
          'chat_endpoint' => 'openai/v1/chat/completions',
          'models' => [
            'llama3-8b-8192' => 'llama3-8b-8192',
          ],
        ],
      ],
    ];

    $form['provider'] = [
      '#type' => 'select',
      '#title' => $this->t('AI Provider'),
      '#options' => array_map(fn($p) => $p['label'], $providers),
      '#default_value' => $provider,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::updateModelDropdown',
        'wrapper' => 'model-wrapper',
      ],
    ];

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $config->get('api_key'),
      '#required' => TRUE,
      '#size' => 100,
      '#maxlength' => 255,
    ];

    $form['model_container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'model-wrapper'],
    ];

    if ($provider && isset($providers[$provider])) {
      $form['model_container']['model'] = [
        '#type' => 'select',
        '#title' => $this->t('Model'),
        '#options' => $providers[$provider]['models'],
        '#default_value' => $config->get('model') ?? array_key_first($providers[$provider]['models']),
        '#required' => TRUE,
      ];

      $form['model_container']['base_uri'] = [
        '#type' => 'value',
        '#value' => $providers[$provider]['base_uri'],
      ];
      $form['model_container']['chat_endpoint'] = [
        '#type' => 'value',
        '#value' => $providers[$provider]['chat_endpoint'],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * AJAX callback to update the model field.
   *
   * This function updates the model dropdown based on the selected provider.
   *
   * @param array $form
   *   The form array to be updated.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array
   *   The updated model container form.
   */
  public function updateModelDropdown(array &$form, FormStateInterface $form_state) {
    return $form['model_container'];
  }

  /**
   * {@inheritdoc}
   *
   * Submits the form values and saves them in the configuration.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $provider = $form_state->getValue('provider');
    $model = $form_state->getValue(['model_container', 'model']);

    if (empty($model)) {
      $model = $form_state->getValue('model');
    }

    $providers = [
      'openai' => [
        'base_uri' => 'https://api.openai.com/v1/',
        'chat_endpoint' => 'chat/completions',
      ],
      'groq' => [
        'base_uri' => 'https://api.groq.com/',
        'chat_endpoint' => 'openai/v1/chat/completions',
      ],
    ];

    $base_uri = $providers[$provider]['base_uri'] ?? '';
    $chat_endpoint = $providers[$provider]['chat_endpoint'] ?? '';

    $this->config('ai_readme_generator.settings')
      ->set('provider', $provider)
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('model', $model)
      ->set('base_uri', $base_uri)
      ->set('chat_endpoint', $chat_endpoint)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
