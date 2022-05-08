<?php

namespace Lib;

class Validator {

  public function __construct(){}

  public function validate_email($email){
    $email_is_valid = preg_match("/^([a-zA-Z0-9\.]+@+[a-zA-Z]+(\.)+[a-zA-Z]{2,3})$/", $email) == 0? true:false;
    return $email_is_valid;

  }

  public function get_jwt($token)
  {
    $check_token = preg_match('/Bearer\s(\S+)/', $token, $matches);
    return $check_token == 0 ? false : $matches[1];
  }

  public function validate_email_with_response($email){
    if($this->validate_email($email)){
      http_response_code(400);
      echo json_encode(array(
        'error' => true,
        'message' => "invalid email",
      ));
      exit();
    }
  }

  private static function bad_request_with_response_msg($message){
    http_response_code(400);
    echo json_encode(array(
      'error' => true,
      'message' => $message,
    ));
    exit();
  }

  public function validate_body($body,$needed_params){
    if(count($needed_params) == 1 && empty($body->{$needed_params[0]})){
      Validator::bad_request_with_response_msg("$needed_params[0] needed");
    }else {
      foreach($needed_params as $value){
        if(empty($body->{$value})){
          Validator::bad_request_with_response_msg("all values needed");
        }
      }
    }
  }

  public function validate_password_length($password,$password_length){
    return (strlen($password) < $password_length) ? false : true;
  }

  public function validate_password_with_response($password,$password_length){
    if(!$this->validate_password_length($password,$password_length)) {
      http_response_code(400);
      echo json_encode(array(
        'error' => true,
        'message' => "password length should be greater than or equal to $password_length",
      ));
      exit();
    }
  }

  public function validate_query_strings($needed_values){
    $query_string_values = array();
    foreach($needed_values as $query_string){
      if(isset($_GET[$query_string])){
        $query_string_values[$query_string] = $_GET[$query_string];
      }else {
        http_response_code(400);
        echo json_encode(array(
          'error' => true,
          'message' => "$query_string not set",
        ));
        exit();
      }
    }
    return $query_string_values;
  }
}

?>
