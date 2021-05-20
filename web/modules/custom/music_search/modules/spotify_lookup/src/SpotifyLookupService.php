<?php

namespace Drupal\spotify_lookup;

use GuzzleHttp\Client;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Component\Serialization\Json;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SpotifyLookupService {

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
   * Spotify credentials
   */
  private $key = '338951373f7c4b75b866b8f390b86e13';
  private $secret = 'd39da6dfbbb641948c1f38836aae2430';
  private $token = '';
  private $SPOTIFY_CACHE_LIFETIME = 3600;

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
   * Sends a GET query to Spotify for specific URL
   *
   * @param $uri string
   *   The fully generated search string
   * @return object
   *   Returns a stdClass with the search results or an error message
   */
  private function _spotify_api_get_query(string $uri) {
    $client = new Client(['base_uri' => 'https://api.spotify.com/v1/']);
    $cache = $this->_spotify_api_get_cache_search($uri);
    $search_results = null;

    if (!empty($cache)) {
      $search_results = $cache;
    }
    else {
      $token = $this->_spotify_api_get_auth_token();
      $token = Json::decode($token);
      $options = [
        'timeout' => 3,
        'headers' => [
          'Accept' => 'application/json',
          'Authorization' => "Bearer " . $token['access_token'],
        ],
      ];

//      $search_results = \Drupal::httpClient()->request($uri, $options);
      $search_results = $client->request('GET', $uri, $options);

      if (empty($search_results->error)) {
        $search_results = Json::decode($search_results->getBody());
        $this->_spotify_api_set_cache_search($uri, $search_results);

      }
      else {
        $this->messenger->addMessage($this->t('The search request resulted in the following error: @error.', [
          '@error' => $search_results->error,
        ]));

        return $search_results->error;
      }
    }

    return $search_results;
  }

  /**
   * Saves a search to Drupal's internal cache.
   *
   * @param string $cid
   *   The cache id to use.
   * @param array $data
   *   The data to cache.
   */
  private function _spotify_api_set_cache_search(string $cid, array $data) {
    \Drupal::cache()->set($cid, $data, time() + $this->SPOTIFY_CACHE_LIFETIME/*,'spotify-api-cache'*/);
  }

  /**
   * Looks up the specified cid in cache and returns if found
   *
   * @param string $cid
   *   Normally a uri with a search string
   *
   * @return array|bool
   *   Returns either the cache results or false if nothing is found.
   */
  private function _spotify_api_get_cache_search(string $cid) {
    $cache = \Drupal::cache()->get($cid)/*, 'spotify-api-cache')*/;
    if (!empty($cache)) {
      if ($cache->expire > time()) {
        return $cache->data;
      }
    }
    return FALSE;
  }

  /**
   * Gets Auth token from the Spotify API
   */
  private function _spotify_api_get_auth_token() {
    $connection_string = "https://accounts.spotify.com/api/token";
    $key = base64_encode($this->key . ':' . $this->secret);
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $connection_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
    curl_setopt($ch, CURLOPT_POST, 1);

    $headers = array();
    $headers[] = "Authorization: Basic " . $key;
    $headers[] = "Content-Type: application/x-www-form-urlencoded";
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);

    curl_close ($ch);
    return $result;
  }

  /**
   * Calls the Spotify's server with given query and receives a response
   */
  public function lookup($query, $type = '') {
    if ($type === '') {
      $type = 'artist,album,track';
    } elseif ($type === 'release') {
      $type = 'album';
    } elseif ($type === 'song') {
      $type = 'track';
    }

    $val = $this->_spotify_api_get_query('search?q='.urlencode($query).'&type='.urlencode($type));

    return $val;
  }
}


