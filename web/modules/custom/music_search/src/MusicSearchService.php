<?php

namespace Drupal\music_search;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\discogs_lookup\DiscogsLookupService;
//use Drupal\music_search\Form\MusicSearchForm;

/**
 * Class MusicSearchService provides basic service in the module
 * @package Drupal\music_search
 */
class MusicSearchService {
  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var DiscogsLookupService;
   */
  protected $discogsService;

//  /**
//   * @var \Drupal\music_search\Form\MusicSearchForm
//   */
//  protected $musicSearchForm;

  public function __construct(ConfigFactoryInterface $configFactory,
                              DiscogsLookupService $discogsService/*,
                              MusicSearchForm $musicSearchForm*/) {
    $this->configFactory = $configFactory;
    $this->discogsService = $discogsService;
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

  public function getDiscogs($query, $type = '') {
    return $this->discogsService->lookup($query, $type);
  }

  /**
   * Saves a file, based on it's type
   *
   * @param $url
   *   Full path to the image on the internet
   * @param $folder
   *   The folder where the image is stored on your hard drive
   * @param $type
   *   Type should be 'image' at all time for images.
   * @param $title
   *   The title of the image (like ALBUM_NAME - Cover), as it will appear in the Media management system
   * @param $basename
   *   The name of the file, as it will be saved on your hard drive
   *
   * @return int|null|string
   * @throws EntityStorageException
   */
  public function _save_file($url, $folder, $type, $title, $basename, $uid = 1) {
    if(!is_dir(\Drupal::config('system.file')->get('default_scheme').'://' . $folder)) {
      return null;
    }
    $destination = \Drupal::config('system.file')->get('default_scheme').'://' . $folder . '/'.basename($basename);
    if(!file_exists($destination)) {
      $file = file_get_contents($url);
      $file = file_save_data($file, $destination);
    }
    else {
      $file = \Drupal\file\Entity\File::create([
        'uri' => $destination,
        'uid' => $uid,
        'status' => FILE_STATUS_PERMANENT
      ]);

      $file->save();
    }

    $file->status = 1;

    $media_type_field_name = 'field_media_image';

    $media_array = [
      $media_type_field_name => $file->id(),
      'name' => $title,
      'bundle' => $type,
    ];
    if($type == 'image') {
      $media_array['alt'] = $title;
    }

    $media_object = \Drupal\media\Entity\Media::create($media_array);
    $media_object->save();
    return $media_object->id();
  }
}
