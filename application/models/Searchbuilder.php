<?php 

class Searchbuilder extends CI_Model {
    function __construct(){
        parent::__construct();
    }

    function default_query_group($search_columns, $value){
        if(!empty($value)){
            $this->read_db->group_start();
            $column_key = 0;
              foreach($search_columns as $column){
                if($column_key == 0) {
                  $this->read_db->like($column,$value,'both'); 
                }else{
                  $this->read_db->or_like($column,$value,'both');
              }
                $column_key++;				
            }
            $this->read_db->group_end();       
          }
    }

    function searchbuilder_query_group($columns){

        $searchBuilder = $this->input->post('searchBuilder');

        if($searchBuilder != null && isset($searchBuilder['criteria'][0]['condition'])){

            $outer_criteria = $searchBuilder['criteria'];
            $outer_logic = $searchBuilder['logic'];
            // log_message('error', json_encode($searchBuilder));
            if(isset($outer_criteria)){
              $this->read_db->group_start();
              $column_key = 0;
              foreach($outer_criteria as $conditions){
                if(array_key_exists('condition', $conditions)){
                  $this->search_builder_condition($conditions,$outer_logic, $columns, $column_key);
                }elseif(array_key_exists('criteria', $conditions)){
                  $inner_criteria = $conditions['criteria'];
                  $inner_logic = $conditions['logic'];
                  $inner_column_key = 0;
                  foreach($inner_criteria as $inner_conditions){
                    if(array_key_exists('condition', $inner_conditions)){
                      $this->search_builder_condition($inner_conditions,$inner_logic, $columns, $inner_column_key);
                    }
                    $inner_column_key++;
                  }
      
                }else{
                  $conditions['condition'] = '=';
                  $this->search_builder_condition($conditions,$outer_logic, $columns, $column_key);
                }
      
                $column_key++;
              }
              $this->read_db->group_end();
            } 
          }
    }

    function search_builder_condition($conditions,$outer_logic, $columns, $column_key){
        // log_message('error', json_encode(['conditions' => $conditions,'outer_logic' => $outer_logic, 'column_key' => $column_key]));
        $list_column = str_replace(' ','_',strtolower(trim($conditions['data'])));
        $column = get_query_column_for_list_column($columns, $list_column, '@');
        $value = isset($conditions['value'][0]) ? $conditions['value'][0] : '';
        $type = $conditions['type'];
        $condition = isset($conditions['condition']) ? $conditions['condition'] : '=';
    
        if($value == 'yes' || $value == 'Yes' || $value == 'YES'){
          $value = 1;
        }elseif($value == 'no' || $value == 'No' || $value == 'NO'){
          $value = 0;
        }

        // log_message('error', json_encode($type));
        
        $condition_key_word_prefix = ''; 
    
              if($column_key != 0 && $outer_logic == 'OR'){
                $condition_key_word_prefix = 'or_';
              }
    
              if($type == 'date'){
                switch($condition){
                  case '<':
                    $this->read_db->{$condition_key_word_prefix.'where'}($column.' < "'.$value.'"');
                    break;
                  case '>':
                    $this->read_db->{$condition_key_word_prefix.'where'}($column.' > "'.$value.'"');
                    break;
                  case 'between':
                    $value2 = isset($conditions['value'][1]) ? $conditions['value'][1] : date('Y-m-d');
                    $this->read_db->{$condition_key_word_prefix.'where'}($column.' >= "'.$value.'" AND '. $column . ' <= "' . $value2.'"' );
                    break;
                  case '!=':
                      $value != '' ? $this->read_db->{$condition_key_word_prefix.'where'}(array($column.' <>' => $value)) : $this->read_db->{$condition_key_word_prefix.'where'}(array($column .' IS NOT NULL' => NULL));
                      break;
                  case '=':
                    $value != '' ? $this->read_db->{$condition_key_word_prefix.'where'}(array($column => $value)) : $this->read_db->{$condition_key_word_prefix.'where'}(array($column => NULL));
                    break;
                  default:
                    log_message('error', json_encode(['missing_operator' => $this->input->post('searchBuilder')]));
                }
              }elseif($type == 'string' || $type == 'html'){
                switch($condition){
                  case 'starts':
                    $this->read_db->{$condition_key_word_prefix.'like'}($column,$value,'after');
                    break;
                  case '!starts':
                      $this->read_db->{$condition_key_word_prefix.'not_like'}($column,$value,'after');
                      break;
                  case 'ends':
                    $this->read_db->{$condition_key_word_prefix.'like'}($column,$value,'before');
                    break;
                  case 'ends':
                      $this->read_db->{$condition_key_word_prefix.'not_like'}($column,$value,'before');
                      break;
                  case 'contains':
                    $this->read_db->{$condition_key_word_prefix.'like'}($column,$value,'both');
                    break;
                  case '!contains':
                      $this->read_db->{$condition_key_word_prefix.'not_like'}($column,$value,'both');
                      break;
                  case '!=':
                    $array_of_values = explode(',',$value);
                    $value != '' ? $this->read_db->{$condition_key_word_prefix.'where_not_in'}($column, $array_of_values) : $this->read_db->{$condition_key_word_prefix.'where'}(array($column.' IS NOT NULL' => NULL));
                    break;
                  case '=':
                    $array_of_values = explode(',',$value);
                    $value != '' ? $this->read_db->{$condition_key_word_prefix.'where_in'}($column, $array_of_values): $this->read_db->{$condition_key_word_prefix.'where'}($column, NULL);
                    break;
                  default:
                    log_message('error', json_encode(['missing_operator' => $this->input->post('searchBuilder')]));
                    
                }
              }elseif($type == 'num' || $type == 'html-num' || $type == 'html-num-fmt' || $type == 'num-fmt'){
                switch($condition){
                  case '<':
                    $this->read_db->{$condition_key_word_prefix.'where'}($column.' < '.$value);
                    break;
                  case '>':
                    $this->read_db->{$condition_key_word_prefix.'where'}($column.' > '.$value);
                    break;
                  case '<=':
                    $this->read_db->{$condition_key_word_prefix.'where'}($column.' <= '.$value);
                    break;
                  case '>=':
                    $this->read_db->{$condition_key_word_prefix.'where'}($column.' >= '.$value);
                    break;
                  case 'between':
                    $value2 = isset($conditions['value'][1]) ? $conditions['value'][1] : date('Y-m-d');
                    $this->read_db->{$condition_key_word_prefix.'where'}($column.' >= "'.$value.'" AND '. $column . ' <= "' . $value2.'"' );
                    break;
                  case '!between':
                      $value2 = isset($conditions['value'][1]) ? $conditions['value'][1] : date('Y-m-d');
                      $this->read_db->{$condition_key_word_prefix.'where'}($column.' < "'.$value.'" OR '. $column . ' > "' . $value2.'"' );
                      break;
                  case '=':
                    $array_of_values = explode(',',$value);
                    $value != '' ? $this->read_db->{$condition_key_word_prefix.'where_in'}($column, $array_of_values) : $this->read_db->{$condition_key_word_prefix.'where'}($column, NULL);
                    break;
                  case '!=':
                      $array_of_values = explode(',',$value);
                      $value != '' ? $this->read_db->{$condition_key_word_prefix.'where_not_in'}($column, $array_of_values): $this->read_db->{$condition_key_word_prefix.'where'}(array($column .' IS NOT NULL' => NULL));
                      break;
                  default:
                    log_message('error', json_encode(['missing_operator' => $this->input->post('searchBuilder')]));
                }
              }
      }
}