<?php
require_once('config.php');
      $route = "user";
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