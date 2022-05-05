<?php
declare(strict_types=1);

namespace Lib;

include_once __DIR__ . "/../../../../config/config.php";
require __DIR__ .  "/../vendor/autoload.php";

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;
use Lib\Response;
use Lib\Validator;

ini_set("display_errors",1);

class Controller
{

  private Response $response;

  public function __construct(){
    $this->response = new Response();
    $this->validator = new Validator();
  }

  public static function get_jwt(string $token)
  {
    $check_token = preg_match('/Bearer\s(\S+)/', $token, $matches);
    return $check_token == 0 ? false : $matches[1];
  }

  private static function get_payload(string $jwt){
    $payload = null;
    try {
      $payload = (JWT::decode($jwt,new Key(config('secret_key'),config('hash'))));
    }catch(\Firebase\JWT\ExpiredException $ex){
      (new Response())->send_response(400,[["error",true],["message",$ex->getMessage()]]);
      exit();
    }
    return $payload;
  }

  public function protected_controller($callback):void
  {
    $token = (getallheaders())['Authorization'] ?? false;
    $body = json_decode(file_get_contents("php://input"));

    if ($token) {

      $jwt = $this->validator->get_jwt($token);

      if ($jwt) {
        $payload = Controller::get_payload($jwt);
        $callback($payload, $body);
      } else {
        $this->response->send_response(400,[["error",true],['message',"invalid jwt"]]);
      }
    } else {
      $this->response->send_response(401,[["error",true],['message',"Authorization header missing"]]);
    }
  }

  public function public_controller($callback):void
  {
    $body = json_decode(file_get_contents("php://input"));
    $callback($body);
  }

  public function access_token_controller($callback)
  {
    $token = (getallheaders())['Authorization'] ?? false;
    $body = json_decode(file_get_contents("php://input"));

    if ($token) {

      $jwt = $this->validator->get_jwt($token);

      if ($jwt) {
        $payload = Controller::get_payload($jwt);
        if($payload->aud == "users"){
          $callback($payload,$body);
        }else {
          $this->response->send_response(400,[["error",true],['message',"access token needed"]]);
        }
      } else {
        $this->response->send_response(400,[["error",true],['message',"invalid jwt"]]);
      }
    } else {
      $this->response->send_response(401,[["error",true],['message',"Authorization header missing"]]);
    }
  }
}
