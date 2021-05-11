<?php

namespace Drupal\music_search;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Config\ConfigFactoryInterface;

class MusicSearchService {
  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory;
  }

  public function getSpotify() {
    $config = $this->configFactory->get('music_world.custom_music_search');
    $music_search = $config->get('music_search');
    if ($music_search !== '' && $music_search) {
      return $music_search;
    }

    return $this->t("Here is your Spotify");
  }

  public function getDiscogs() {
    return $this->t("Here is your stupid Discogs!");
  }
}
