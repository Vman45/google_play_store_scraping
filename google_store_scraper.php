<?php
  
  class GoogleStoreScraper {

    public function fetchAndPersist($url) {
      // Get the DOM object for the url that was passed
      $dom = $this->_getDomObjectFromUrl($url);
      $app = array();

      // Save app URL
      $app['url'] = $url;
      // Parse app title
      $app['title'] = $dom->getElementsByTagName('title')->item(0)->nodeValue;
      // Parse company
      $app['company'] = $this->_getChildElementByAttribute($dom, 'span', 'itemprop', 'name');
      // Parse app score
      $app['score'] = $this->_getChildElementByAttribute($dom, 'div', 'class', 'score');
      // Parse app last update
      $app['last_update'] = $this->_getChildElementByAttribute($dom, 'div', 'itemprop', 'datePublished');
      // Parse app downloads
      $app['downloads'] = $this->_getChildElementByAttribute($dom, 'div', 'itemprop', 'numDownloads');
      // Parse app version
      $app['version'] = $this->_getChildElementByAttribute($dom, 'div', 'itemprop', 'softwareVersion');
      // Parse app comments (only grab the first 5 comments)
      $app['comments'] = $this->_getComments($dom, 5);
      // Parse app ratings
      $app['ratings'] = $this->_getRatings($dom);

      // Save parsed app data to the database
      $app['saved_to_db'] = $this->_saveToDb($app);

      // Return the app array so that it can be logged
      return $app;
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

    private function _getChildElementByAttribute($parent, $tagName, $attribute, $attributeShouldMatch) {
      foreach($parent->getElementsByTagName($tagName) as $element) {
        if($element->getAttribute($attribute) === $attributeShouldMatch){
          return trim($element->nodeValue);
        }
      }
    }

    private function _getComments($dom, $maxCommentsToParse) {
      $comments = [];
      foreach($dom->getElementsByTagName('div') as $element) {
        if($element->getAttribute('class') === 'single-review'){
          // Only get the max number of comments that we want to parse
          if(sizeof($comments) < $maxCommentsToParse){
            $comment = array(
              'author' => $this->_getChildElementByAttribute($element, 'span', 'class', 'author-name'), 
              'date' => $this->_getChildElementByAttribute($element, 'span', 'class', 'review-date'),
              'score' => $this->_getChildElementByAttribute($element, 'div', 'class', 'tiny-star star-rating-non-editable-container'),
              'text' => $this->_getChildElementByAttribute($element, 'div', 'class', 'review-body with-review-wrapper')
            );
            array_push($comments, $comment);
          }
        }
      }
      return $comments;
    }

    private function _getRatings($dom) {
      $ratings = array();
      foreach($dom->getElementsByTagName('div') as $element) {
        // App nb5 rating
        if($element->getAttribute('class') === 'rating-bar-container five'){
          $ratings['nb5'] = $this->_getChildElementByAttribute($element, 'span', 'class', 'bar-number');
        }
        // App nb4 rating
        if($element->getAttribute('class') === 'rating-bar-container four'){
          $ratings['nb4'] = $this->_getChildElementByAttribute($element, 'span', 'class', 'bar-number');
        }
        // App nb3 rating
        if($element->getAttribute('class') === 'rating-bar-container three'){
          $ratings['nb3'] = $this->_getChildElementByAttribute($element, 'span', 'class', 'bar-number');
        }
        // App nb2 rating
        if($element->getAttribute('class') === 'rating-bar-container two'){
          $ratings['nb2'] = $this->_getChildElementByAttribute($element, 'span', 'class', 'bar-number');
        }
        // App nb1 rating
        if($element->getAttribute('class') === 'rating-bar-container one'){
          $ratings['nb1'] = $this->_getChildElementByAttribute($element, 'span', 'class', 'bar-number');
        }
      }
      return $ratings;
    }

    // This method checks to see if we scraped enough information to make this
    // app worth saving. It can be adjusted by adding/removing the required
    // pieces of data that we'd like to required before inserting a record in the DB.
    private function _shouldSaveToDb($app) {
      return $app['title'] &&
        $app['company'] &&
        $app['score'] &&
        $app['downloads'];
    }

    private function _saveToDb($app) {
      $savedToDb = $this->_shouldSaveToDb($app);
      $servername = "localhost";
      $username = "dbadmin";
      $password = "";
      $dbname = "zefflin_google_play_store";
      $conn = new mysqli($servername, $username, $password, $dbname);
      if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
      } 

      // NOTE This query will only insert the text from the first comment.
      $sql = 'INSERT INTO GoogleApps (title, company, score, rating_nb5, rating_nb4, rating_nb3, rating_nb2, rating_nb1, last_update, downloads, version, comments)
      VALUES (
        "' . $conn->real_escape_string($app['title']) . '", 
        "' . $conn->real_escape_string($app['company']) . '", 
        "' . $app['score'] . '", 
        "' . $app['ratings']['nb5'] . '", 
        "' . $app['ratings']['nb4'] . '", 
        "' . $app['ratings']['nb3'] . '", 
        "' . $app['ratings']['nb2'] . '", 
        "' . $app['ratings']['nb1'] . '", 
        "' . $app['last_update'] . '", 
        "' . $app['downloads'] . '", 
        "' . $app['version'] . '", 
        "' . $conn->real_escape_string($app['comments'][0]['text']) .'"
      )';

      if ($this->_shouldSaveToDb($app) && $conn->query($sql) === FALSE) {
        $savedToDb = false;
        echo("Error: " . $sql . "<br>" . $conn->error);
      }
      $conn->close();

      // Return if we saved the record to the database or not
      return $savedToDb;
    }
}

