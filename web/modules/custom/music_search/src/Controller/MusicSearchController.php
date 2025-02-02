<?php

namespace Drupal\music_search\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\music_search\MusicSearchService;
use Drupal\spotify_lookup\SpotifyLookupService;
use Drupal\discogs_lookup\DiscogsLookupService;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for Music Search
 * @return array
 *    Some message in a render array
 */
class MusicSearchController extends ControllerBase {

  /**
   * Tempstore service
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Messenger service
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Music Search service
   *
   * @var MusicSearchService
   */
  protected $service;

  /**
   * Spotify Lookup service
   *
   * @var SpotifyLookupService
   */
  protected $spotifyService;

  /**
   * Discogs Lookup service
   *
   * @var DiscogsLookupService
   */
  protected $discogsService;

  private $TYPES = [
    'label' => 'Publisher',
    'release' => 'Album',
    'master' => 'Album',
    'artist' => 'Artist'
  ];

  /**
   * Inject services
   */
  public function __construct(PrivateTempStoreFactory $tempStoreFactory,
                              MessengerInterface $messenger,
                              MusicSearchService $service,
                              SpotifyLookupService $spotifyService,
                              DiscogsLookupService $discogsService) {
    $this->tempStoreFactory = $tempStoreFactory;
    $this->messenger = $messenger;
    $this->service = $service;
    $this->spotifyService = $spotifyService;
    $this->discogsService = $discogsService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('messenger'),
      $container->get('music_search.service'),
      $container->get('music_search.spotify.service'),
      $container->get('music_search.discogs.service'),
    );
  }

  /**
   * Returns response for the autocompletion
   *
   * @param Request $request
   *  The current request object containing the search string
   *
   * @return JsonResponse
   *  A JSON response containing the autocomplete suggestion
   */
  public function autocomplete(Request $request) {
    $query = $request->query->get('q');
    $matches = [];
    if (strlen($query) >= 3) {
      $spotifyResults = $this->spotifyLookup($query);

      foreach ($spotifyResults as $type) {
        foreach ($type['items'] as $item) {
          $matches[] = [
            'value' => $item['name'].' ['.$item['id'].']',
            'label' => '<img src="'.end($item['images'])['url'].'" width="32" height="32"/>'.' ['.ucwords($item['type']).'] '.$item['name'].' (Spotify)'
          ];
        }
      }

      $discogsResults = $this->discogsLookup($query);

      foreach ($discogsResults['results'] as $row) {
        $matches[] = [
          'value' => $row['title'].' ['.$row['id'].']',
          'label' => '<img src="'.$row['thumb'].'" width="32" height="32"/>'.' ['.$this->TYPES[$row['type']].'] '.$row['title'].' (Discogs)'
        ];
      }
    }
    $tempstore = $this->tempStoreFactory->get('music_search');
    $tempstore->set('matches', $matches);
    return new JsonResponse($matches);
  }

  /**
   * @param $query
   * @return array
   */
  public function spotifyLookup($query, $type = '') {
    $json_arr = $this->spotifyService->lookup($query, $type);
    return $json_arr;
  }

  /**
   * @param $query
   * @return array
   */
  public function discogsLookup($query, $type = '') {
    $json_array = $this->discogsService->lookup($query, $type);
    return $json_array;
  }
}
