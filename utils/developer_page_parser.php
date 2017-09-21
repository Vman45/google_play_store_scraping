<?php
  
  class DeveloperPageParser {

    private $_baseUrl = 'https://play.google.com';

    public function fetchAppUrlsFromDeveloperPage($url) {
      // Get the DOM object for the url that was passed
      $dom = $this->_getDomObjectFromUrl($url);
      $urls = array();
      // Parse the app urls from this page
      foreach($dom->getElementsByTagName('a') as $a) {
        if($a->getAttribute('class') === 'card-click-target'){
          array_push($urls, $this->_baseUrl . trim($a->getAttribute('href')));
        }
      }
      // Remove duplicates
      $uniqueUrls = array_unique($urls);
      return $uniqueUrls;
    }

    private function _getDomObjectFromUrl($url) {
      $ch = curl_init();
      // Set connection timeout in seconds
      $timeout = 10;
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
      $html = curl_exec($ch);
      curl_close($ch);
      // Create a DOM parser object
      $dom = new DOMDocument();
      // Parse the HTML from the provided URL.
      // The @ before the method call suppresses any warnings that
      // loadHTML might throw because of invalid HTML in the page.
      @$dom->loadHTML($html);
      return $dom;
    }
  }

