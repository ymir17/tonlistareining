<?php

namespace Drupal\music_search\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form definition for Music Search
 */
class MusicSearchConfigurationForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['music_search.custom_music_search'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'music_search_configuration_form';
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   * @return array
   *  form
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('music_search.custom_music_search');

    $form['music_search'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Music Search'),
      '#description' => $this->t('Type out what you want to search for...'),
      '#default_value' => $config->get('music_search'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $music_search = $form_state->getValue('music_search');
    if(strlen($music_search) > 20) {
      $form_state->setErrorByName('music_search', $this->t('This is too long '));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('music_search.custom_music_search')
      ->set('music_search', $form_state->getValue('music_search'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
