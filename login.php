<?php
require_once('config.php');

if($_SERVER['REQUEST_METHOD'] == "POST") :
  $data = json_decode(file_get_contents('php://input'), true);
  //print_r($data);
  $sql = "SELECT * FROM user WHERE email = :email AND password = :password";
  $args['email'] = $data['email'];
  $args['password'] = md5($data['password']);

  $rq = $db->prepare($sql);
  $rq->execute($args);

  if($rq->rowCount() > 0) :
    $rows = $rq->fetch(PDO::FETCH_ASSOC);
    unset($rows['password']);
    $response['message'] = "vous êtes connecté";
    $response['data'] = $rows;
    $_SESSION['user'] = $rows;
    $_SESSION['token'] = md5(date("DdMYHiS"));
    $response['token'] = $_SESSION['token'];
    echo json_encode($response);
  else :
    $response['message'] = 'error de log/pass';
    http_response_code(401);
    echo json_encode($response);
  endif;

elseif($_SERVER['REQUEST_METHOD'] == "DELETE") :
  unset($_SESSION['user']);
  unset($_SESSION['token']);
  $response['message'] = "Déconnecté";
  echo json_encode($response);
else :
  $response['message'] = "Method not allowed";
  http_response_code(405);
  echo json_encode($response);
endif;