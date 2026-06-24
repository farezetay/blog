<?php 
//print_r($_GET);
//echo $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
require 'config.php';
include 'functions.php';

$routes_ok = ["user", "post", "comment", "category", "login", "register"];
$methods_ok = ["GET", "POST", "PUT", "DELETE"];

$method = $_SERVER['REQUEST_METHOD'];
$route = (isset($_GET['route'])) ? $_GET['route'] : '';  

$response = [];
// si la route utilisée ne correspond pas à nos routes,
switch($route):
  case "posts":
    $route = "post";
  break;
  case "comments":
    $route = "comment";
    
  break;
  case "categories":
    $route = "category";
  break;
endswitch;
if(!in_array($route, $routes_ok)):
  //on affiche "bad route", on affiche le code erreur et on stop tout
    $response['message'] = "Bad route";
    $response['status'] = 403;
    echo json_encode($response);
    http_response_code(403);
    die();
endif;
// si la méthode utilisée ne correspond pas à nos méthodes,
if(!in_array($method, $methods_ok)):
  // on affiche "methode not allowed", on affiche le code erreur et on stop tout
    $response['message'] = "Method not allowed";
    $response['status'] = 405;
    echo json_encode($response);
    http_response_code(405);
    die();
endif;

if($route == "login"):
  include('login.php');
  die();
endif;

if($route == "register" AND $method == "POST"):
  include('register.php');
  die();
endif;

if($method != "GET"):
  // et qu'on à pas de token donc qu'on est pas connecté,
  if(!isset($_SESSION['token'])):
    //on affiche "vous n'êtes pas connecté" et le code erreur puis on stop tout
    $response['message'] = "Vous n'êtes pas connecté";
    http_response_code(403);
    echo json_encode($response);
    die();
  else:
    // si on a bien un token, on créé une variable qui aura la valeur donnée par la fonction getAuthorization
    $token = getAuthorization();
    //echo $token;
    // si la valeur du token est différente du token de la session déjà ouverte, 
    if($token != $_SESSION['token']):
      // on affiche le message "token invalid", le code erreur et on stop tout
      $response['message'] = "Token invalid";
      http_response_code(403);
      echo json_encode($response);
      die();
    endif;
  endif;
endif;



switch($method):
  // si on utilise la méthode DELETE
    case "DELETE":
      // mais qu'on à pas donné d'id
      if(!isset($_GET['id'])):
        // on affiche "il manque un id", le code erreur et on stop tout
        $response['message'] = "il manque un id";
        $response['status'] = 403;
        http_response_code(403);
        echo json_encode($response);
        die();
      endif;
    if($_SESSION['user']['role'] != "administrateur"):
       $sql = "SELECT * FROM {$route} WHERE id = :id";
       $args = [];
       $args['id'] = $_GET['id'];
       $rq = $db->prepare($sql);
       $rq->execute($args);
       $row = $rq->fetch(PDO::FETCH_ASSOC);
       if($row['user_id'] != $_SESSION['user']['ID']):
          $response['message'] = "Vous n'avez pas les droits";
          $response['status'] = 403;
          http_response_code(403);
          echo json_encode($response);
          die();
       endif;
    endif;
      // si on a bien un id, on utilise la méthode DELETE sur la route sélectionnée et on affiche le résultat
      $sql = "DELETE FROM $route WHERE id = :id";
      $args['id'] = $_GET['id'];
      $rq = $db->prepare($sql);
      $rq->execute($args);
      $response['message'] = "$route {$_GET['id']} supprimé";
      echo json_encode($response);
      die();
    break;
  // si on utilise la méthode POST
    case "POST":
      // on récupère les valeur qu'on veut introduire dans la DB
      $data = json_decode(file_get_contents('php://input'), true);
      $sql = "INSERT INTO $route SET ";
      // pour chaque valeur on récupère le contenu
      foreach($data AS $field => $value) : 
        $sql .= "$field = :$field,";
        $args[$field] = $value;
      endforeach;
      // on retire les virgule entre les valeur pour les mettre dans l'url et on utilise la bonne url
      $sql = rtrim($sql,',') ;
      echo $sql;
      $rq = $db->prepare($sql);
      $rq->execute($args);
      $insert_id = $db->lastInsertID();
      $response['message']= "$route : $insert_id créé";
      echo json_encode($response);
      die();
    break;
    // si on à pas d'id
    if(!isset($_GET['id'])):
        $response['message'] = "il manque un id";
        $response['status'] = 403;
        http_response_code(403);
        echo json_encode($response);
        die();
    endif;
  // si on utilise la méthode PUT
    case "PUT":
      if($_SESSION['user']['role'] != "administrateur"):
       $sql = "SELECT * FROM {$route} WHERE id = :id";
       $args = [];
       $args['id'] = $_GET['id'];
       $rq = $db->prepare($sql);
       $rq->execute($args);
       $row = $rq->fetch(PDO::FETCH_ASSOC);
       if($row['user_id'] != $_SESSION['user']['ID']):
          $response['message'] = "Vous n'avez pas les droits";
          $response['status'] = 403;
          http_response_code(403);
          echo json_encode($response);
          die();
       endif;
    endif;
      // on récupère les valeurs qu'on veut introduire dans la DB
      $data = json_decode(file_get_contents('php://input'), true);
      // on fait la même chose qu'en POST mais au lieu d'un INSERT on fait un UPDATE
      $sql = "UPDATE $route SET ";
      foreach($data AS $field => $value) : 
        $sql .= "$field = :$field,";
        $args[$field] = $value;
      endforeach;
      $sql = rtrim($sql,',') ;
      $sql .= " WHERE id = :id";
      $args['id'] = $_GET['id'];
      //echo $sql;
      $rq = $db->prepare($sql);
      $rq->execute($args);
      $response['message']= "$route : {$_GET['id']} édité";
      echo json_encode($response);
      die();
    break;
  // si on a acune des méthodes au dessus, on fait un SELECT 
    default: //méthode GET
      $sql = "SELECT * FROM {$route}";
      $args = [];
      if(isset($_GET['id'])):
        $sql .= " WHERE ID = :id";
        $args['id'] = $_GET['id'];
      endif;
        $rq = $db->prepare($sql);
        $rq->execute($args);
        $nb_hits = $rq->rowCount();
        $response['nb_hits'] = $nb_hits;
        $rows = $rq->fetchAll(PDO::FETCH_ASSOC);
        $response['results'] = $rows;
        

        //myPrint_r($rows);

        echo json_encode($response);
endswitch;


