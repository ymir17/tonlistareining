<?php

namespace Drupal\music_search;

use Drupal\Core\StringTranslation\StringTranslationTrait;

class MusicSearchService {
  use StringTranslationTrait;

  public function getSpotify() {
    return $this->t("Here is your Spotify");
  }
}
