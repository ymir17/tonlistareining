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
    return ['music_search.config'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'music_search_admin_form';
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   * @return array
   *  form
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('music_search.config');

    $form['search'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search'),
      '#description' => $this->t('Search for an artist, album or a song.'),
      '#default_value' => $config->get('music_search'),
    ];
    $form['next'] = [
      '#type' => 'submit',
      '#value' => $this->t('Next')
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $music_search = $form_state->getValue('search');
    if(strlen($music_search) == 0) {
      $form_state->setErrorByName('music_search', $this->t('Field cannot be empty'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('music_search.config')
      ->set('music_search_config', $form_state->getValue('music_search_config'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
