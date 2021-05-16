<?php

namespace Drupal\music_search\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\music_search\MusicSearchService;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The edit form that follows MusicSearchForm
 */
class EditMusicForm extends FormBase {

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
   * @var \Drupal\music_search\MusicSearchService
   */
  protected $service;

  /**
   * Constants of types
   * @var string[]
   */
  private $TYPES = [
    'label' => 'Publisher',
    'release' => 'Album',
    'master' => 'Album',
    'artist' => 'Artist'
  ];

  /**
   * Constructs a new MusicSearchForm object
   */
  public function __construct(
    MessengerInterface $messenger,
    LoggerChannelFactoryInterface $logger_factory,
    PrivateTempStoreFactory $tempStoreFactory,
    MusicSearchService $musicSearchService
  ) {
    $this->messenger = $messenger;
    $this->loggerFactory = $logger_factory;
    $this->tempStoreFactory = $tempStoreFactory;
    $this->service = $musicSearchService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('logger.factory'),
      $container->get('tempstore.private'),
      $container->get('music_search.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'music_search_edit_form';
  }

  /**
   * Initiate search on both Discogs and Spotify and merges the result
   * @returns array
   */
  private function initLookups($query, $type) {
    $discogs_res = $this->service->getDiscogs($query, $type);
//    $spotify_res = $this->service->getSpotify($query, $type);

//    $res = array_merge_recursive($discogs_res, $spotify_res);
    return $discogs_res;  // TODO: Change to:  return $res
  }

  private function getArtistForm($query) {
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $query,
    ];
    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#options' => [
        'artist' => $this->t('Musician'),
        'album' => $this->t('Record'),
        'song' => $this->t('Song'),
      ],
      '#default_value' => 'artist',
    ];
    $form['table'] = [];
    return $form;
  }

  private function getAlbumForm($query) {
    $form['img'] = [
      '#type' => 'image',
      '#title' => 'Image',
    ];
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $query,
    ];
    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#options' => [
        'artist' => $this->t('Musician'),
        'album' => $this->t('Record'),
        'song' => $this->t('Song'),
      ],
      '#default_value' => 'album',
    ];
    $form['table'] = [];
    return $form;
  }

  private function getSongForm($query) {
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $query,
    ];
    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#options' => [
        'artist' => $this->t('Musician'),
        'album' => $this->t('Record'),
        'song' => $this->t('Song'),
      ],
      '#default_value' => 'song',
    ];
    $form['table'] = [];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $tempstore = $this->tempStoreFactory->get('music_search');
    $params = $tempstore->get('params');
    $query = $params['query'];
    $type = $params['type'];

    $discogsRes = $this->service->getDiscogs($query);
//    $spotifyRes = $this->service->getSpotify($query);
    $headerDiscogs = [
      'thumb' => $this->t('Thumbnail'),
      'title' => $this->t('Title'),
      'type' => $this->t('Type'),
      'discogs_id' => $this->t('Discogs ID'),
    ];
//    $headerSpotify = [];
    switch ($type) {
      case 'artist':
        $form = $this->getArtistForm($query);
        break;
      case 'album':
        $form = $this->getAlbumForm($query);
        break;
      case 'song':
        $form = $this->getSongForm($query);
        break;
    }

    $optionsDiscogs = [];
    foreach ($discogsRes['results'] as $row) {
      $optionsDiscogs[] = [
        'thumb' => [
          'data' => [
            '#type' => 'markup',
            '#markup' => '<img src="'.$row['thumb'].'" width="100px" height="100px"/>',
          ],
        ],
        'type' => $this->t($this->TYPES[$row['type']]),
        'title' => $row['title'],
        'discogs_id' => $row['id'],
      ];
    }

    $form['tableDiscogs'] = [
      '#type' => 'tableselect',
      '#caption' => [
        '#markup' => '<h2><strong>'.$this->t('Data from Discogs').'</strong></h2>'
      ],
      '#header' => $headerDiscogs,
      '#options' => $optionsDiscogs,
      '#empty' => $this->t('Discogs came out empty')
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Submit')
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
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement submitForm() method.
  }
}
