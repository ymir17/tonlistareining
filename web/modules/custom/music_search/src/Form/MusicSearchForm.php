<?php

namespace Drupal\music_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search\Form\SearchPageFormBase;

class MusicSearchForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'music_search_page_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
//    $config = $this->config('music_search.config');

    $form['artist'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Music Search'),
      '#description' => $this->t('Type out what you want to search for...'),
//      '#default_value' => $config->get('music_search'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Next')
    ];

    return $form;
//    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (strlen($form_state->getValue('artist')) == 0) {
      $form_state->setErrorByName('artist', $this->t('Field cannot be empty'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // 1. Set the $params array with the values of the form
    // to save those values in the store.
    $params['url'] = $form_state->getValue('url');
    $params['items'] = $form_state->getValue('items');
    // 2. Create a PrivateTempStore object with the collection 'ex_form_values'.
    $tempstore = $this->tempStoreFactory->get('ex_form_values');
    // 3. Store the $params array with the key 'params'.
    try {
      $tempstore->set('params', $params);
      // 4. Redirect to the simple controller.
      $form_state->setRedirect('ex_form_values.simple_controller_show_item');
    }
    catch (\Exception $error) {
      // Store this error in the log.
      $this->loggerFactory->get('ex_form_values')->alert(t('@err', ['@err' => $error]));
      // Show the user a message.
      $this->messenger->addWarning(t('Unable to proceed, please try again.'));
    }
  }
}
