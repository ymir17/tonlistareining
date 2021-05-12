<?php

namespace Drupal\music_search\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\music_search\MusicSearchService;
use Drupal\C;
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
   * The Music Search service.
   * @var MusicSearchService
   */
  protected $service;

  /**
   * MusicSearchController.php constructor.
   * @param MusicSearchService $service
   */
  public function __construct(MusicSearchService $service) {
    $this->service = $service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('music_search.service')
    );
  }

  /**
   * Music Search
   * @return array
   *  some message
   */
  public function musicSearch() {
    return [
      '#markup' => $this->service->getSpotify()
    ];
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
    $string = $request->query->get('q');
    $matches = [];
    $db = \Drupal::database();
    $query = $db->select('node_field_data', 'n')
      ->fields('n', ['nid', 'title', 'type'])
      ->condition('title', $string . '%', 'LIKE')
      ->execute()
      ->fetchAll();
    foreach ($query as $row) {
      $matches[] = [
        'value' => $row->nid,
        'label' => $row->title
      ];
    }
    return new JsonResponse($matches);
//    if ($string) {
//      $matches = [];
//      $query = \Drupal::entityQuery('node')
//        ->condition('status', 1)
//        ->condition('title', '%'.db_like($string).'%', 'LIKE');
//      $nids = $query->execute();
//      $result = entity_load_multiple()
//    }
//    $db = \Drupal::database();
//    $result = $db->select('artist', 'n')
//      ->fields('n');
  }
}
