<?php

namespace Lib;

class Pagination {

  protected $connection;
  private $page;
  private $results_per_page;
  private $tbl_name;
  private $needed_attributes;
  private $user_id;

  public function __construct($connection,$tbl_name,$needed_attributes,$params){
    $this->connection = $connection;
    $this->tbl_name = $tbl_name;
    $this->needed_attributes = $needed_attributes;
    $this->page = !empty($params['page']) && $params['page'] >= 1 ?$params['page'] : 1;
    $this->results_per_page = !empty($params['results_per_page']) && $params['results_per_page'] >= 1 ? $params['results_per_page']:10;
    $this->user_id = !empty($params['user_id']) ? $params['user_id'] : null;
  }

  private function get_data(){
    if($this->user_id){
      $query = "SELECT * FROM $this->tbl_name WHERE user_id = ?";
      $stmt = $this->connection->prepare($query);
      $stmt->bind_param("i",$this->user_id);
      $stmt->execute();
      return $stmt->get_result();
    }
    $query = "SELECT * FROM $this->tbl_name";
    $stmt = $this->connection->query($query);
    return $stmt;
  }

  protected function tbl_row_length(){
    return $this->get_data()->num_rows;
  }

  protected function get_page_results(){
    $page_results = ($this->page-1) * $this->results_per_page;
    return $page_results;
  }

  protected function get_number_of_results(){
    return $this->tbl_row_length();
  }

  protected function get_number_of_pages(){
    $number_of_results = $this->tbl_row_length();
    $number_of_pages = ceil($number_of_results/$this->results_per_page);
    return $number_of_pages;
  }

  private function get_page_data(){
    $page_results = $this->get_page_results();
    $stmt =null;
    $result = null;
    $data = array();
    if($this->user_id){
      $query = "SELECT * FROM $this->tbl_name WHERE user_id = ? LIMIT $page_results, $this->results_per_page";
      $stmt = $this->connection->prepare($query);
      $stmt->bind_param("i",$this->user_id);
      $stmt->execute();
      $result = $stmt->get_result();
    }else {
      $query = "SELECT * FROM $this->tbl_name LIMIT $page_results, $this->results_per_page";
      $result = $this->connection->query($query);
    }


    while($row = $result->fetch_assoc()){
      $entity = array();
      foreach ($this->needed_attributes as $item) {
        $entity[$item] = $row[$item];
      }
      array_push($data,$entity);
    }
    return $data;
  }

  protected function create_page_properties(){
    [$next_page,$prev_page,$has_next,$has_prev] = [null,null,false,false];

    if($this->page <= 1 && $this->get_number_of_pages() <= 1){
      [$next_page,$prev_page,$has_next,$has_prev] = [null,null,false,false];
    }else if($this->page <= 1 && $this->get_number_of_pages() > 1){
      $next_page = $this->page + 1;
      $has_next = true;
    }else if($this->page == $this->get_number_of_pages()){
      $prev_page = $this->page - 1;
      $has_prev = true;
    }else if($this->page > $this->get_number_of_pages()){
      [$next_page,$prev_page,$has_next,$has_prev] = [null,null,false,false];
    }else {
      $next_page = $this->page + 1;
      $prev_page = $this->page - 1;
      $has_next = true;
      $has_prev = true;
    }

    return [$next_page,$prev_page,$has_next,$has_prev];
  }

  public function meta_data(){
    $page_properties = $this->create_page_properties();
    return array(
      'data' => $this->get_page_data(),
      'current_page' => $this->page,
      'next_page' => $page_properties[0],
      'prev_page' => $page_properties[1],
      'has_next' => $page_properties[2],
      'has_prev' => $page_properties[3],
      'total_results' => $this->get_number_of_results(),
      'number_of_pages' => $this->get_number_of_pages()
    );
  }

}
