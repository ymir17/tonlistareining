<?php

namespace Drupal\discogs_lookup;

use GuzzleHttp\Client;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Component\Serialization\Json;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
//use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class DiscogsLookupService
 * @package Drupal\discogs_lookup
 */
class DiscogsLookupService {

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

  private $KEY = 'ZTTfWenqRIBJqcNkwnxR';

  private $SECRET = 'oKuGvfNImmewMWrSLTXIcctphUKWbFrB';

  private $URI = 'https://api.discogs.com';

  private $TYPES = [
    'artist' => 'artists',
    'release' => 'releases',
    'song' => 'songs',
  ];

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
   * Calls the Discogs' server with given query and receives a response
   */
  public function lookup($query, $type = '') {
//    $header = ['User-Agent' => 'music-search/0.1 +https://tonlistareining.ddev.site'];

    $client = new Client(['base_uri' => $this->URI]);
    $promise = $client->requestAsync(
      'GET',
      '/database/search',
      ['query' => [
          'q' => $query,
          'type' => $type,
          'per_page' => '5',
          'key' => $this->KEY,
          'secret' => $this->SECRET
        ],
      ]
    );

    $response = $promise->wait();

//    var_dump($response);
    return Json::decode($response->getBody());
  }

  public function getById($id, $type) {
    $client = new Client(['base_uri' => $this->URI]);
    $promise = $client->requestAsync(
      'GET',
      '/'.$this->TYPES[$type].'/'.$id,
      ['query' => [
        'per_page' => '5',
        'key' => $this->KEY,
        'secret' => $this->SECRET
        ],
      ]
    );

    $response = $promise->wait();

//    var_dump($response);
    return Json::decode($response->getBody());
  }
}
