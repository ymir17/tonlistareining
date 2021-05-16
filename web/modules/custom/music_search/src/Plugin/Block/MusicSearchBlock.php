<?php

namespace Drupal\music_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\music_search\Form\MusicSearchForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\music_search\MusicSearchService;

/**
 * Music Search block
 * @block(
 *   id = "music_search_service_block",
 *   admin_label = @Translation("Music Search service")
 * )
 */
class MusicSearchBlock extends BlockBase implements ContainerFactoryPluginInterface {
  /**
   * @var \Drupal\music_search\MusicSearchService
   */
  protected $music_search_service;

  /**
   * @var \Drupal\music_search\Form\MusicSearchForm
   */
  protected $musicSearchForm;

  /**
   * MusicSearchBlock constructor.
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param MusicSearchService $music_search_service
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition,
                              MusicSearchService $music_search_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->music_search_service = $music_search_service;
  }

  /**
   * @return array
   */
  public function build() {
    return [
      '#markup' => $this->music_search_service->getSpotify(),
    ];
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('music_search.service')
    );
  }
}
