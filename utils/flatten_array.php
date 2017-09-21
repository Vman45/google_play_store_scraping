<?php
  
  function flattenArray($array) {
    $final = array();
    $it = new RecursiveIteratorIterator(new RecursiveArrayIterator($array));
    foreach($it as $v) {
      array_push($final, $v);
    }
    return $final;
  }

