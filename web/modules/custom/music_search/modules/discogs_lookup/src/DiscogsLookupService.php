<?php

namespace Drupal\discogs_lookup;

//use Discogs\ClientFactory;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use GuzzleHttp\Client;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Tests\Core\DependencyInjection\Compiler\ServiceClassDefault;
use DrupalCodeGenerator\Command\Drupal_8\ServiceProvider;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * @var Client
   */
  private $client;

  /**
   * Constructs a new MusicSearchForm object
   */
  public function __construct(
    MessengerInterface $messenger,
    LoggerChannelFactoryInterface $logger_factory,
    PrivateTempStoreFactory $tempStoreFactory,
    Client $client
  ) {
    $this->messenger = $messenger;
    $this->loggerFactory = $logger_factory;
    $this->tempstoreFactory = $tempStoreFactory;
    $this->client = $client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('logger.factory'),
      $container->get('tempstore.private'),
      $container->get('http_client')
    );
  }

  /**
   * Calls the Discogs' server with given query and receives a response
   */
  public function lookup() {
  $credentials = '&key=ZTTfWenqRIBJqcNkwnxR&secret=oKuGvfNImmewMWrSLTXIcctphUKWbFrB';
//    $client = ClientFactory::factory([
//      'defaults' => [
//        'headers' => ['User-Agent' => 'music-search/0.1 +https://tonlistareining.ddev.site'],
//        'query' => [
//          'key' => 'ZTTfWenqRIBJqcNkwnxR',
//          'secret' => 'oKuGvfNImmewMWrSLTXIcctphUKWbFrB',
//        ],
//      ],
//    ]);

    $tempstore = $this->tempstoreFactory->get('music_search');
    $params = $tempstore->get('params');
    $query = $params['query'];

//    $response = $client->search([
//      'q' => $query
//    ]);

    $promise = $this->client->getAsync(
      'https://api.discogs.com/database/search?q='.$query.$credentials,
      ['headers' => ['User-Agent' => 'music-search/0.1 +https://tonlistareining.ddev.site']]);
    return $promise->then(
      function (ResponseInterface $res) {
        return $res;
      },
      function (RequestException $e) {
        echo $e->getMessage();
        return $e;
      }
    );
  }
}
