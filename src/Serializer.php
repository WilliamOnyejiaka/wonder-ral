<?php

namespace Lib;

class Serializer {

  private $needed_attributes;

  public function __construct($needed_attributes){
    $this->needed_attributes = $needed_attributes;
  }

  public function tuple($result){
    if($result->num_rows == 0){
      return [];
    }else {
      $data = [];
      while($row = $result->fetch_assoc()){
        $values = [];
        foreach($this->needed_attributes as $attr){
          $data[$attr] = $row[$attr];
        }
      }
      return $data;
    }
  }

  public function dump_all($result){
    if($result->num_rows == 0){
      return [];
    }else {
      $data = [];
      while($row = $result->fetch_assoc()){
        $values = [];
        foreach($this->needed_attributes as $attr){
          $values[$attr] = $row[$attr];
        }

        array_push($data,$values);
      }
      return $data;
    }
  }
}
