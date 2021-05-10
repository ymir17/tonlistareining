<?php

namespace Drupal\music_search\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\music_search\MusicSearchService;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
      '#markup' => $this->service->getSpotify(),
    ];
  }
}
