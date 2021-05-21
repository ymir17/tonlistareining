<?php

namespace Drupal\music_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\music_search\MusicSearchService;
use Drupal\spotify_lookup\SpotifyLookupService;
use Drupal\discogs_lookup\DiscogsLookupService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The last form in the series.
 * Provides the user to edit the final result of the content.
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
   * @var \Drupal\spotify_lookup\SpotifyLookupService
   */
  protected $spotifyService;

  /**
   * @var \Drupal\discogs_lookup\DiscogsLookupService
   */
  protected $discogsService;

  /**
   * Constructs a new EditMusicForm object
   */
  public function __construct(
    MessengerInterface $messenger,
    LoggerChannelFactoryInterface $logger_factory,
    PrivateTempStoreFactory $tempStoreFactory,
    MusicSearchService $musicSearchService,
    SpotifyLookupService $spotifyService,
    DiscogsLookupService $discogsService
  ) {
    $this->messenger = $messenger;
    $this->loggerFactory = $logger_factory;
    $this->tempStoreFactory = $tempStoreFactory;
    $this->service = $musicSearchService;
    $this->spotifyService = $spotifyService;
    $this->discogsService = $discogsService;
  }

  /**
   * @inheritDoc
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('logger.factory'),
      $container->get('tempstore.private'),
      $container->get('music_search.service'),
      $container->get('music_search.spotify.service'),
      $container->get('music_search.discogs.service')
    );
  }

  /**
   * @inheritDoc
   */
  public function getFormId() {
    return 'music_search_edit_form';
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $tempstore = $this->tempStoreFactory->get('music_search');
    $params = $tempstore->get('params');
    $ids = $params['ids'];
    $type = $params['type'];

    $results = [];
    foreach($ids as $id) {
      if (ctype_digit($id)) {  // If True then it's Discogs, else Spotify
        $results[$id] = $this->discogsService->getById($id, $type);
      } else {
        $results[$id] = $this->spotifyService->getById($id, $type);
      }
    }
    $tempstore->set('results', $results);

    $header = [
      'title' => $this->t('Title'),
      'artist' => $this->t('Artist'),
      'images' => $this->t('Images'),
      'label' => $this->t('Label'),
      'release_date' => $this->t('Release Date'),
      'genre' => $this->t('Genre'),
    ];

    $form['select'] = [
      '#type' => 'select',
      '#title' => $this->t('Select which table below you want to import'),
      '#options' => [
        's' => $this->t('Spotify'),
        'd' => $this->t('Discogs')
      ],
      '#empty_option' => $this->t('-select-'),
      '#weight' => -2
    ];
    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Next')
      ],
      '#weight' => -1
    ];

    $optionsSpot = [];
    $optionsDisc = [];
    foreach ($results as $id => $row) {
      if (ctype_digit($id)) {
        $optionsDisc[$id] = [
          'title' => $row['title'],
          'artist' => $row['artists'][0]['name'],
          'images' => [
            'data' => [
              '#markup' => '<img src="'.$row['images'][0]['resource_url'].'" width="100" />'
            ]
          ],
          'label' => $row['labels'][0]['name'],
          'release_date' => $row['released'],
          'genre' => implode(', ', $row['genres'])
        ];
      } else {
        $optionsSpot[$id] = [
          'title' => $row['name'],
          'artist' => $row['artists'][0]['name'],
          'images' => [
            'data' => [
              '#markup' => '<img src="'.$row['images'][0]['url'].'" width="100"/>'
            ]
          ],
          'label' => $row['label'],
          'release_date' => $row['release_date'],
          'genre' => implode(', ', $row['genres'])
        ];
      }
    }

    $form['tableDisc'] = [
      '#type' => 'table',
      '#caption' => [
        '#markup' => '<h2><strong>'.$this->t('Discogs').'</strong></h2>'
      ],
      '#header' => $header,
      '#rows' => $optionsDisc

    ];
    $form['tableSpot'] = [
      '#type' => 'table',
      '#caption' => [
        '#markup' => '<h2><strong>'.$this->t('Spotify').'</strong></h2>'
      ],
      '#header' => $header,
      '#rows' => $optionsSpot
    ];

    return $form;
  }

  public function buildForm_old(array $form, FormStateInterface $form_state) {
    $tempstore = $this->tempStoreFactory->get('music_search');
    $params = $tempstore->get('params');
//    $matches = $tempstore->get('matches');
    $query = $params['query'];
//    $discogsIDs = $params['discogsIDs'];
//    $spotifyIDs = $params['spotifyIDs'];
    $ids = $params['ids'];
    $type = $params['type'];

    $results = [];
    foreach($ids as $id) {
      if (ctype_digit($id)) {  // If True then it's Discogs, else Spotify
        $results[$id] = $this->discogsService->getById($id, $type);
      } else {
        $results[$id] = $this->spotifyService->getById($id, $type);
      }
    }

    $header = [
      'empty' => '',
      'discogs' => $this->t('Discogs'),
      'spotify' => $this->t('Spotify'),
      'select' => $this->t('Select')
    ];

    $leftmostCol = [
      ['#markup' => '<strong>'.$this->t('Title').'</strong>'],
      ['#markup' => '<strong>'.$this->t('Artist').'</strong>'],
      ['#markup' => '<strong>'.$this->t('Images').'</strong>'],
      ['#markup' => '<strong>'.$this->t('Label').'</strong>'],
      ['#markup' => '<strong>'.$this->t('Release Date').'</strong>'],
      ['#markup' => '<strong>'.$this->t('Genre').'</strong>'],
    ];

    $options = [];
    $select = [
      '#type' => 'select',
      '#options' => [
        'spotify' => 'Spotify',
        'discogs' => 'Discogs'
      ],
      '#default_value' => $this->t('Select')
    ];
    $form['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#caption' => [
        'data' => [
          '#markup' => '<h2><strong>'.$this->t('Finalize the data').'</strong></h2>'],
      ],
    ];
    $rows = [
      [
        'empty' => [
          'data' => $leftmostCol[0]
        ],
      ],
      [
        'empty' => [
          'data' => $leftmostCol[1]
        ],
      ],
      [
        'empty' => [
          'data' => $leftmostCol[2]
        ],
      ],
      [
        'empty' => [
          'data' => $leftmostCol[3]
        ],
      ],
      [
        'empty' => [
          'data' => $leftmostCol[4]
        ],
      ],
      [
        'empty' => [
          'data' => $leftmostCol[5]
        ],
      ]
    ];
    //  ctype_digit($id) === True ? Discogs : Spotify
    foreach ($results as $id => $row) {
      if (ctype_digit($id)) {
        $rows[0]['discogs'] = $row['title'];
        $rows[1]['discogs'] = $row['artists'][0]['name'];
        $rows[2]['discogs'] = ['data' => [
          '#markup' => '<img src="'.$row['images'][0]['resource_url'].'" width="100" />']];
        $rows[3]['discogs'] = $row['labels'][0]['name'];
        $rows[4]['discogs'] = $row['released'];
        $rows[5]['discogs'] = implode(', ', $row['genres']);
      } else {
        $rows[0]['spotify'] = $row['name'];
        $rows[1]['spotify'] = $row['artists'][0]['name'];
        $rows[2]['spotify'] = ['data' => [
          '#markup' => '<img src="'.$row['images'][0]['url'].'" width="100"/>']];
        $rows[3]['spotify'] = $row['label'];
        $rows[4]['spotify'] = $row['release_date'];
        $rows[5]['spotify'] = implode(', ', $row['genres']);
      }
    }
    for ($i=0; $i<6; $i++) {
      $rows[$i]['select'] = ['data' => $select];
    }

    $form['table']['#rows'] = $rows;
    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Save')
      ],
      '#weight' => -1
    ];

    return $form;
  }

  /**
   * @inheritDoc
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (strlen($values['select']) <= 0) {
      $form_state->setErrorByName('select', $this->t('You must select a table to continue'));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $selected = $form_state->getValues()['select'];
    $tempstore = $this->tempStoreFactory->get('music_search');
    $tables = $tempstore->get('results');

    $result = [];
    foreach ($tables as $id => $table) {
      if (ctype_digit($id) && $selected == 'd') {
        $result = $table;
      } elseif (!ctype_digit($id) && $selected == 's') {
        $result = $table;
      }
    }
    $breakpoint=1;
  }
}
