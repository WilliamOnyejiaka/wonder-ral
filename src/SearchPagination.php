<?php

namespace Lib;

use Lib\Pagination;

class SearchPagination extends Pagination {

  private $keyword;
  private $search_params;

  public function __construct($connection,$tbl_name,$needed_attributes,$keyword,$search_params,$params){
    parent::__construct($connection,$tbl_name,$needed_attributes,$params);
    $this->connection = $connection;
    $this->tbl_name = $tbl_name;
    $this->needed_attributes = $needed_attributes;
    $this->page = !empty($params['page']) && $params['page'] >= 1 ?$params['page'] : 1;
    $this->results_per_page = !empty($params['results_per_page']) && $params['results_per_page'] >= 1 ? $params['results_per_page']:10;
    $this->user_id = !empty($params['user_id']) ? $params['user_id'] : null;
    $this->keyword = $keyword;
    $this->search_params = $search_params;
  }

  protected function tbl_row_length(){
    return $this->get_data()->num_rows;
  }

  protected function get_number_of_pages(){
    $number_of_results = $this->tbl_row_length();
    $number_of_pages = ceil($number_of_results/$this->results_per_page);
    return $number_of_pages;
  }

  private function get_search_string(){
    $search_string = "";
    for($i = 0;$i < count($this->search_params);$i++) {
      $feild = $this->search_params[$i] ;
      if($i == count($this->search_params)-1){
        $search_string .= "$feild LIKE '%$this->keyword%'";
      }else{
        $search_string .= "$feild LIKE '%$this->keyword%' OR ";
      }
    }
    return $search_string;
  }

  private function get_data(){
    $search_string = $this->get_search_string();
    $query = "SELECT * FROM $this->tbl_name WHERE user_id = ? AND ($search_string)";

    $stmt = $this->connection->prepare($query);

    $stmt->bind_param("i",$this->user_id);
    $stmt->execute();
    return $stmt->get_result();
  }

  private function get_page_data(){
    $page_results = $this->get_page_results();
    $search_string = $this->get_search_string();
    $query = "SELECT * FROM $this->tbl_name WHERE user_id = ? AND ($search_string) LIMIT $page_results, $this->results_per_page";
    $stmt = $this->connection->prepare($query);
    $stmt->bind_param("i",$this->user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = array();

    while($row = $result->fetch_assoc()){
      $entity = array();
      foreach ($this->needed_attributes as $item) {
        $entity[$item] = $row[$item];
      }
      array_push($data,$entity);
    }
    return $data;
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
