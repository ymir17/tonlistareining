<?php

// #ddev-generated: Automatically generated Drupal settings file.
if (file_exists($app_root . '/' . $site_path . '/settings.ddev.php') && getenv('IS_DDEV_PROJECT') == 'true') {
include $app_root . '/' . $site_path . '/settings.ddev.php';
include $app_root . '/' . $site_path . '/settings.local.php';
}
else if (getenv('STAGING_SERVER') == 'true') {
include $app_root . '/' . $site_path . '/settings.staging.php';
}
else {
include $app_root . '/' . $site_path . '/settings.production.php';
}