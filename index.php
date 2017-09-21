<?php
  
  // Set the max execution time in php.ini to something larger than 30 seconds (default)
  ini_set('max_execution_time', 5000);

  include('./utils/google_store_scraper.php');
  include('./utils/developer_page_parser.php');
  include('./utils/flatten_array.php');
  
  $dataScraped = array();
  $developerPageParser = new DeveloperPageParser();
  $scraper = new GoogleStoreScraper();

  $developerPageUrls = array_map('str_getcsv', file('./urls/developer_page_urls.csv' , FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
  $appPageUrls = flattenArray(array_map('str_getcsv', file('./urls/app_page_urls.csv' , FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)));

  // Fetch app page urls from developer pages
  foreach ($developerPageUrls as $url) {
    // Only feed in developer page urls into this class (example: https://play.google.com/store/apps/developer?id=Facebook)
    // If app page urls are fed into it, it will grab all the linked apps from the bottom of the app page, which is not what we want
    $newUrls = $developerPageParser->fetchAppUrlsFromDeveloperPage($url[0]);
    $appPageUrls = array_merge($appPageUrls, $newUrls);
  }

  // Parse each app page url
  foreach ($appPageUrls as $url) {
    array_push($dataScraped, $scraper->fetchAndPersist($url));
  }

  // Clear the log file before we add the scraped app data to it
  file_put_contents('log.json', '');
  file_put_contents('log.json', json_encode($dataScraped), FILE_APPEND | LOCK_EX);
