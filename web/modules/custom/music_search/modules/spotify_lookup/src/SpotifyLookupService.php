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
   * Requests authorization
   */
  public function requestAuth() {

    $client = new GuzzleHttp\Client();
    $promise = $client->request('GET', 'https://accounts.spotify.com/authorize', [
      'query' => [
        'client_id' => $this->key,
        'response_type' => 'code',
        'redirect_uri' => 'https://tonlistareining.ddev.site/music_search']
      ]
    );

    $response = $promise->wait();

    $breyta = 'strengur';
    $breyta = 10;

    return $response;

  }

  /**
   * Calls the Spotify's server with given query and receives a response
   */
  public function lookup($query) {

    $val = $this->requestAuth();

    return $val;
  }
}


