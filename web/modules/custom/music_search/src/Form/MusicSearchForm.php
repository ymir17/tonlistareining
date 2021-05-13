<?php

namespace Drupal\music_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\pathauto\MessengerInterface;
use Drupal\search\Form\SearchPageFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MusicSearchForm
 * @package Drupal\music_search\Form
 */
class MusicSearchForm extends FormBase {

  /**
   * Drupal\Core\Messenger\MessengerInterface definition
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Drupal\Core\Logger\LoggerChannelFactoryInterface definition
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Drupal\Core\TempStore\PrivateTempStoreFactory definition
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempstoreFactory;

  /**
   * Constructs a new MusicSearchForm object
   */
  public function _construct(
    MessengerInterface $messenger,
    LoggerChannelFactoryInterface $logger_factory,
    PrivateTempStoreFactory $tempStoreFactory
  ) {
    $this->messenger = $messenger;
    $this->loggerFactory = $logger_factory;
    $this->tempstoreFactory = $tempStoreFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('logger.factory'),
      $container->get('tempstore.private')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'music_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
//    $config = $this->config('music_search.config');

    $form['query'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Music Search'),
      '#autocomplete_route_name' => 'music_search.autocomplete',
      '#description' => $this->t('Type out what you want to search for...'),
//      '#default_value' => $config->get('music_search'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Next')
      ]
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (strlen($form_state->getValue('query')) == 0) {
      $form_state->setErrorByName('query', $this->t('Field cannot be empty'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // 1. Set the $params array with the values of the form
    // to save those values in the store.
    $params['query'] = $form_state->getValue('query');
//    $params['items'] = $form_state->getValue('items');
    // 2. Create a PrivateTempStore object with the collection 'ex_form_values'.
    $tempstore = $this->tempStoreFactory->get('music_search_form_values');  // TODO: Figure out what this is, giving problems!
    // 3. Store the $params array with the key 'params'.
    try {
      $tempstore->set('params', $params);
      // 4. Redirect to the simple controller.
      $form_state->setRedirect('music_search.edit_form');
    }
    catch (\Exception $error) {
      // Store this error in the log.
      $this->loggerFactory->get('ex_form_values')->alert(t('@err', ['@err' => $error]));
      // Show the user a message.
      $this->messenger->addWarning(t('Unable to proceed, please try again.'));
    }
  }
}
