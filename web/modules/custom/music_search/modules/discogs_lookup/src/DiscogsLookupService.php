<?php

namespace Drupal\discogs_lookup;

//use Discogs\ClientFactory;
use GuzzleHttp\Client;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Component\Serialization\Json;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

//use Symfony\Component\HttpFoundation\JsonResponse;

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
  public function lookup() {
    $tempstore = $this->tempstoreFactory->get('music_search');
    $params = $tempstore->get('params');
    $query = $params['query'];

    $key = 'ZTTfWenqRIBJqcNkwnxR';
    $secret = 'oKuGvfNImmewMWrSLTXIcctphUKWbFrB';
//    $credentials = '&key='.key.'&secret='.$secret;
    $uri = 'https://api.discogs.com';
    $header = ['User-Agent' => 'music-search/0.1 +https://tonlistareining.ddev.site'];

    $client = new Client(['base_uri' => $uri]);
    $promise = $client->requestAsync(
      'GET',
      '/database/search', [
        'query' => [
          'q' => $query,
          'key' => $key,
          'secret' => $secret
        ],
      ]
    );

    $response = $promise->wait();

    var_dump($response);
//    echo $response->getBody();
    return Json::decode($response->getBody());
  }
}
