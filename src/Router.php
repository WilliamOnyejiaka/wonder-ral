<?php
declare(strict_types=1);
namespace Lib;
ini_set('display_errors',1);

class Router {

  private array $handlers;
  private const METHOD_GET = "GET";
  private const METHOD_POST = "POST";
  private const METHOD_PUT = "PUT";
  private const METHOD_PATCH = "PATCH";
  private const METHOD_DELETE = "DELETE";
  private $callback_404;
  private $callback_405;
  private string $uri_path_start;
  private $allow_cors;

  public function __construct($uri_path_start,$allow_cors){
    $this->allow_cors = $allow_cors;
    $this->uri_path_start = $uri_path_start;
  }

  public function get(string $path,$callback):void {
    $this->add_handler(self::METHOD_GET,$path,$callback);
  }

  public function post(string $path,$callback):void {
    $this->add_handler(self::METHOD_POST,$path,$callback);
  }

  public function put(string $path,$callback):void {
    $this->add_handler(self::METHOD_PUT,$path,$callback);
  }

  public function patch(string $path,$callback):void {
    $this->add_handler(self::METHOD_PATCH,$path,$callback);
  }

  public function delete(string $path,$callback):void {
    $this->add_handler(self::METHOD_DELETE,$path,$callback);
  }

  private function add_handler(string $method,string $path,$callback):void {
    $this->handlers[$method . $path] = [
      'path' => $path,
      'method' => $method,
      'callback' => $callback,
    ];
  }

  private function activate_cors(){
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
    header("Access-Control-Allow-Credentials: true");
    $method = $_SERVER['REQUEST_METHOD'];
    if ($method == "OPTIONS") {
      header('Access-Control-Allow-Origin: *');
      header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method,Access-Control-Request-Headers, Authorization");
      header("HTTP/1.1 200 OK");
      die();
    }
  }

  public function add_405_callback($callback){
    $this->callback_405 = $callback;
  }

  private function get_request_path():string {
    $request_uri = parse_url($_SERVER['REQUEST_URI']);
    $paths =  explode("/",$request_uri['path']);
    $requestPath = "";
    $start_index = null;
    for($i = 0;$i < count($paths);$i++){
       if($paths[$i] === $this->uri_path_start){
         $start_index = $i+1;
         break;
       }
    }

    if(!$start_index){
      echo "Not";
      exit();
    }
    for($i = $start_index;$i < count($paths);$i++){
       $requestPath = $requestPath ."/" . $paths[$i];
    }


    // $request_uri = parse_url($_SERVER['REQUEST_URI']);
    // $paths =  explode("/",$request_uri['path']);
    // $requestPath = "";
    // for($i = 6;$i < count($paths);$i++){
    //    $requestPath = $requestPath ."/" . $paths[$i];
    // }
    return $requestPath;
  }

  public function add_404_callback($callback){
    $this->callback_404 = $callback;
  }

  private function invoke_callback($callback){
    ($this->allow_cors && $this->activate_cors());
    header('Content-Type: application/json');

    if(!$callback){
      header("HTTP/1.0 404 Not Found");
      if(!empty($this->callback_404)){
        $callback = $this->callback_404;
      }else {
        $callback = function() {
          echo "Not Found";
        };
      }
    }
    if(is_string($callback)){
      $parts = explode('::',$callback);
      if(is_array($parts)){
        $classname = array_shift($parts);
        $class = new $classname;

        $method = array_shift($parts);
        $callback = [$class,$method];
      }
    }
    call_user_func_array($callback,[array_merge($_GET,$_POST)]);
  }

  public function run():void {
    $request_path = $this->get_request_path();
    $callback = null;
    $method = $_SERVER['REQUEST_METHOD'];

    foreach ($this->handlers as $handler) {
      if($handler['method'] === $method && $handler['path'] === $request_path){
        $callback = $handler['callback'];
        break;
      }

      if($handler['method'] !== $method && $handler['path'] === $request_path){
        if(!empty($this->callback_405)){
          $callback = $this->callback_405;
        }else {
          $callback = function() {
            echo "Method Not Allowed";
          };
        }
      }
    }

    $this->invoke_callback($callback);
  }
}
?>
