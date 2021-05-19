<?php

namespace Drupal\music_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Messenger\MessengerInterface;
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
  protected $tempStoreFactory;

  /**
   * Constructs a new MusicSearchForm object
   */
  public function __construct(
    MessengerInterface $messenger,
    LoggerChannelFactoryInterface $logger_factory,
    PrivateTempStoreFactory $tempStoreFactory
  ) {
    $this->messenger = $messenger;
    $this->loggerFactory = $logger_factory;
    $this->tempStoreFactory = $tempStoreFactory;
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

    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('I\'m searching for '),
      '#options' => [
        'artist' => $this->t('Artist'),
        'release' => $this->t('Album'),
        'song' => $this->t('Song'),
      ],
      '#default_value' => 'artist',
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
    $params['type'] = $form_state->getValue('type');
    // 2. Create a PrivateTempStore object with the collection 'ex_form_values'.
    $tempstore = $this->tempStoreFactory->get('music_search');
    // 3. Store the $params array with the key 'params'.
    try {
      $tempstore->set('params', $params);
      // 4. Redirect to the simple controller.
      $form_state->setRedirect('music_search.edit_form');
    }
    catch (\Exception $error) {
      // Store this error in the log.
      $this->loggerFactory->get('music_search')->alert(t('@err', ['@err' => $error]));
      // Show the user a message.
      $this->messenger->addWarning(t('Unable to proceed, please try again.'));
    }
  }
}
