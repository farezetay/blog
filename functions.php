<?php
include_once "headers.php";
function myPrint_r($value) {
  if($_ENV['MODE'] == 'dev'):
  echo '<pre>';
    print_r($value);
    echo '</pre>';
  endif;
};

function getAuthorization() {
  //$headers = getallheaders();
  if($_SERVER['HTTP_AUTHORIZATION']):
    $token = $_SERVER['HTTP_AUTHORIZATION'];
    $token = explode(' ', $token);
    $token = $token[1];
    return $token;
  else:
    return false;
  endif;
};

session_start();