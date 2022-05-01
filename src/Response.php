<?php
declare(strict_types = 1);

namespace Lib;

class Response {
  private $response_data;

  public function __construct(){

  }

  public function send_response(int $response_code,array $response_data):void{
    $this->set_response_data($response_data);
    http_response_code($response_code);
    echo json_encode($this->create_response_array());
  }

  private function set_response_data(array $response_data){
    $this->response_data = $response_data;
  }

  private function create_response_array(){
    $response_array = [];
    foreach ($this->response_data as $value) {
      $response_array[$value[0]] = $value[1];
    }
    return $response_array;
  }
}

?>
