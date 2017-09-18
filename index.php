<?php
  
  // Set the max execution time in php.ini to something larger than 30 seconds (default)
  ini_set('max_execution_time', 500);

  include('google_store_scraper.php');
  
  $dataScraped = array();
  $scraper = new GoogleStoreScraper();

  $csv = array_map('str_getcsv', file('google_play_urls.csv' , FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));

  foreach ($csv as $group) {
    foreach ($group as $url) {
      array_push($dataScraped, $scraper->fetchAndPersist($url));
    }
  }

  // Clear the log file before we add the scraped app data to it
  file_put_contents('log.json', '');
  file_put_contents('log.json', json_encode($dataScraped), FILE_APPEND | LOCK_EX);
