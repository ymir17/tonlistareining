<?php

namespace Drupal\music_search;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Config\ConfigFactoryInterface;
//use Drupal\music_search\discogs_lookup\DiscogsLookupService;
//use Drupal\music_search\Form\MusicSearchForm;

class MusicSearchService {
  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

//  /**
//   * @var DiscogsLookupService;
//   */
//  protected $discogsLookupService;

//  /**
//   * @var \Drupal\music_search\Form\MusicSearchForm
//   */
//  protected $musicSearchForm;

  public function __construct(ConfigFactoryInterface $configFactory/*,
                              DiscogsLookupService $discogsLookupService*/) {
    $this->configFactory = $configFactory;
//    $this->discogsLookupService = $discogsLookupService;
  }

  public function getSpotify() {
    $config = $this->configFactory->get('music_world.config');
    $music_search_config = $config->get('music_search');
    if ($music_search_config && $music_search_config !== '') {
      return $music_search_config;
    }

    return $this->t('I know nothing...');
//    return $this->musicSearchForm;
  }

  public function getDiscogs() {
    return $this->t("Here is your stupid Discogs!");
  }
}
