<?php

namespace Drupal\music_search\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\music_search\MusicSearchService;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\views\Ajax\ScrollTopCommand;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * The edit form that follows MusicSearchForm
 */
class ResultListForm extends FormBase {

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
    'artist' => 'Artist',
    'release' => 'Album',
    'master' => 'Album',
    'album' => 'Album',
    'song' => 'Song',
    'track' => 'Song',
  ];

  /**
   * Constants of content types
   * @var string[]
   */
  private $ENTITY_TYPE = [
    'Publisher' => 'publisher',
    'Album' => 'record',
    'Artist' => 'musician',
    'Song' => 'song'
  ];

  /**
   * Constructs a new ResultListForm object
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
    return 'music_search_list_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $tempstore = $this->tempStoreFactory->get('music_search');
    $params = $tempstore->get('params');
    $matches = $tempstore->get('matches');
    $query = $params['query'];
    $id = $params['id'];
    $type = $params['type'];
//    $isMultiple = $type !== 'artist';

    $spotifyRes = $this->service->getSpotify($query, $type);
    $discogsRes = $this->service->getDiscogs($query, $type);

    $headerSpotify = [
      'thumb' => $this->t('Thumbnail'),
      'title' => $this->t('Title'),
      'type' => $this->t('Type'),
      'spotify_id' => $this->t('Spotify ID'),
    ];
    $headerDiscogs = [
      'thumb' => $this->t('Thumbnail'),
      'title' => $this->t('Title'),
      'type' => $this->t('Type'),
      'discogs_id' => $this->t('Discogs ID'),
    ];

    $optionsSpotify = [];
    $spotifyType = '';
    $spotifyImg = '';
    if ($type === 'release') {
      $spotifyType = 'albums';
    } elseif ($type === 'song') {
      $spotifyType = 'tracks';
    } elseif ($type === 'artist') {
      $spotifyType = 'artists';
    }
    foreach ($spotifyRes[$spotifyType]['items'] as $row) {
      if ($spotifyType === 'albums' or $spotifyType === 'artists') {
        $arrSize = count($row['images']);
        if ($arrSize > 0) {
          $spotifyImg = $row['images'][0]['url'];
        }
      } else if ($spotifyType === 'tracks') {
        $arrSize = count($row['album']['images']);
        if ($arrSize > 0) {
          $spotifyImg = ['album']['images'][0]['url'];
        }
      }
        $optionsSpotify[$row['id']] = [
          'thumb' => [
            'data' => [
              '#type' => 'markup',
              '#markup' => '<img src="'.$spotifyImg.'" width="100px" height="100px"/>',
              '#value' => $spotifyImg,
            ],
          ],
          'type' => $this->t($this->TYPES[$row['type']]),
          'title' => $row['name'],
          'spotify_id' => $row['id'],
        ];
    }

      $optionsDiscogs = [];
      foreach ($discogsRes['results'] as $row) {
      $optionsDiscogs[$row['id']] = [
        'thumb' => [
          'data' => [
            '#type' => 'markup',
            '#markup' => '<img src="'.$row['thumb'].'" width="100px" height="100px"/>',
            '#value' => $row['thumb'],
          ],
        ],
        'type' => $this->t($this->TYPES[$row['type']]),
        'title' => $row['title'],
        'discogs_id' => $row['id'],
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Next')
      ],
      '#weight' => -1
    ];

    $form['tableSpotify'] = [
      '#type' => 'tableselect',
      '#caption' => [
        '#markup' => '<h2><strong>'.$this->t('Data from Spotify').'</strong></h2>'
      ],
      '#header' => $headerSpotify,
      '#options' => $optionsSpotify,
      '#default_value' => [$id => 1],
      '#empty' => $this->t('Spotify came out empty'),
      '#multiple' => false,
    ];

    $form['tableDiscogs'] = [
      '#type' => 'tableselect',
      '#caption' => [
        '#markup' => '<h2><strong>'.$this->t('Data from Discogs').'</strong></h2>'
      ],
      '#header' => $headerDiscogs,
      '#options' => $optionsDiscogs,
      '#default_value' => [$id => true],
      '#empty' => $this->t('Discogs came out empty'),
      '#multiple' => false,
    ];

//    /**
//     * Supposed to scroll to the top, doesn't work, disabling it.
//     */
//    $form['scroll'] = [
//      '#type' => 'button',
//      '#value' => $this->t('Scroll to top'),
//      '#ajax' => [
//        'callback' => '::ScrollTopCommand',
//        'event' => 'click'
//      ],
//      '#disabled' => true
//    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (empty($values['tableDiscogs']) && empty($values['tableSpotify'])) {
      $form_state->setErrorByName('actions', $this->t('You must select at least one item on either list'));
    }
  }

  private function getBasenameFromURI($uri) {
    return ltrim(strrchr($uri, '/'), '/');
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $selected = [$values['tableDiscogs'], $values['tableSpotify']];
    $tempstore = $this->tempStoreFactory->get('music_search');
    $params = $tempstore->get('params');
    $params['ids'] = $selected;
    $tempstore->set('params', $params);
//    $tempstore->set('discogsIDs', $selectedDiscogs);
//    $tempstore->set('spotifyIDs', $selectedSpotify);
//    $dataDiscogs = [];
//    foreach ($selectedDiscogs as $id => $val) {
//      $data = $form_state->getCompleteForm()['tableDiscogs']['#options'][$id];
//      $type = (array)$data['type'];
//      $dataDiscogs[$id] = [
//        'thumb' => $data['thumb']['data']['#value'],
//        'type' => $type[' * string'],  // No idea why it has to be '[NUL]*[NUL]string', but that is the way it has to be!
//        'title' => $data['title'],
//      ];
//    }
////    $dataDiscogs = $form_state->get('complete_form')['tableDiscogs'];
////    $selectedSpotify = array_filter($form_state['values']['tableSpotify']);
////    $query = $db->insert('node_field_data');
//    $entity = \Drupal::entityTypeManager()->getStorage('node');
//    foreach ($dataDiscogs as $id => $val) {
//      $this->service->_save_file(
//        $val['thumb'],
//        $val['type'].'_image',
//        'image',
//        $val['title'],
//        $this->getBasenameFromURI($val['thumb']),
//      );
//
//      switch ($this->ENTITY_TYPE[$val['type']]) {
//        case 'musician':
//          $node = $entity
//            ->create([
//              'type' => 'musician',
//              'title' => $val['title']
//            ]);
//          break;
//        case 'record':
//          $node = $entity
//            ->create([
//              'type' => 'record',
//              'title' => $val['title']
//            ]);
//          break;
//        case 'song':
//          $node = $entity
//            ->create([
//              'type' => 'song',
//              'title' => $val['title']
//            ]);
//          break;
//        case 'publisher':
//          $node = $entity
//            ->create([
//              'type' => 'publisher',
//              'title' => $val['title']
//            ]);
//          break;
//      }
//      $node->save();
//    }
    $form_state->setRedirect('music_search.edit');
  }
}
