<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


/**
 *  @package grants <Finance management system for NGOs>
 *	@author  : Nicodemus Karisa
 *  @Modified By: Livingstone Onduso <londuso@ke.ci.org>
 *	@date		 Written On: 20th Aug, 2021 | Modified On 15th June 2023
 *  @method void __construct() main method, first to be executed and initializes variables.
 *  @method void index() has no method body.
 *  @method void get_menu_list() empty method.
 *  @method void already_injected(): Checks if the cheque has been injected
 *  @method void over_cancelled_cheque(): Checks if the cheques has reached the cancellation thresholds
 *  @method void negate_cheque_number(): Updates the voucher record by negating the cancelled cheque.
 *  @method void cheque_to_be_injected_exists_in_range(): Finds the cheques in a range of existing cheque books.
 *  @method void check_count_of_cancelled_cheques(): Counts how many time a cheque has been cancelled.
 *	@see https://techsysnow.com
 */

class Cheque_injection extends MY_Controller
{

  function __construct()
  {
    parent::__construct();
    $this->load->library('cheque_injection_library');
    $this->load->model("cheque_book_model");
  }

  function index()
  {
  }
  static function get_menu_list()
  {
  }

  /**
   *already_injected(): Checks if the cheque has been injected
   * @author Livingstone Onduso: Dated 08-06-2023
   * @access public
   * @return void - echo already_injected string
   * @param int $office_bank_id, $cheque_number
   */
  function already_injected(int $office_bank_id, int $cheque_number): void
  {

    $injected_chqs = $this->cheque_book_model->injected_cheque_exists($office_bank_id, $cheque_number);

    $injected = '';

    if ($injected_chqs==1) {
      $injected = 'already_injected';
    }
    echo $injected;
  }
  /**
   *over_cancelled_cheque(): Checks if the cheques has reached the cancellation thresholds
   * @author Livingstone Onduso: Dated 08-06-2023
   * @access public
   * @return void - echo already_injected string
   * @param int $office_bank_id, $cheque_number
   */
  // function over_cancelled_cheque(int $office_bank_id, int $cheque_number): void
  // {

  //   $count_of_chqs_greater_than_threshold = $this->cheque_book_model->count_of_cancelled_chqs_more_than_three($office_bank_id, $cheque_number);

  //   echo $count_of_chqs_greater_than_threshold;
  // }
  /**
   *negate_cheque_number(): Updates the voucher record by negating the cancelled cheque
   * @author Livingstone Onduso: Dated 08-06-2023
   * @access public
   * @return void - echo 1 or 0
   */

  public function negate_cheque_number(): void
  {

    $post = $this->input->post();

    $office_bank_id = $post['office_bank_id'];

    $cheque_number = $post['cheque_number'];

    echo json_encode($this->cheque_book_model->negate_cheque_number($office_bank_id, $cheque_number));
  }
  /**
   *cheque_to_be_injected_exists_in_range(): Finds the cheques in a range of existing cheque books
   * @author Livingstone Onduso: Dated 08-06-2023
   * @access public
   * @return void - echo 1 or 0
   * @param int $office_bank_id, int $cheque_number
   */
  function cheque_to_be_injected_exists_in_range(int $office_bank_id, int $cheque_number):void
  {

    $resp = $this->cheque_book_model->cheque_to_be_injected_exists_in_range($office_bank_id, $cheque_number);

    echo json_encode($resp);
  }

 /**
   *check_count_of_cancelled_cheques(): Counts how many time a cheque has been cancelled
   * @author Livingstone Onduso: Dated 10-06-2023
   * @access public
   * @return void - echo 1 or 0
   * @param int $office_bank_id, int $cheque_number
   */
  function check_count_of_cancelled_cheques(int $office_bank_id, int $cheque_number):void
  {

    echo json_encode($this->cheque_book_model->count_of_cancelled_chqs($office_bank_id, $cheque_number));
  }

  
  /*
  //OLD Working code with A hidden bug
  function validate_cheque_number_is_cancelled()
  {

    $post = $this->input->post();

    $is_valid_cheque = 0;

    $office_bank_id = $post['office_bank_id'];
    $cheque_number = $post['cheque_number'];

    $invalid_cheque_numbers_for_injection = [];

    $this->load->model('voucher_model');

    // Should not be in the range of any book
    $this->read_db->select(array('cheque_book_start_serial_number', 'cheque_book_count_of_leaves'));
    $this->read_db->where(array('fk_office_bank_id' => $office_bank_id));
    $cheque_books_obj = $this->read_db->get('cheque_book');

    if ($cheque_books_obj->num_rows() > 0) {
      $cheque_books = $cheque_books_obj->result_array();

      foreach ($cheque_books as $cheque_book) {
        $cheque_numbers = range($cheque_book['cheque_book_start_serial_number'], $cheque_book['cheque_book_start_serial_number'] + ($cheque_book['cheque_book_count_of_leaves'] - 1));

        $invalid_cheque_numbers_for_injection = array_merge($invalid_cheque_numbers_for_injection, $cheque_numbers);
      }
    }

    //New Code added by Onduso to correct the bug that is allowing chq injection  of a chq that exists in opening outsanding. Added on 2/06/2023
    
    //Should not be in the list of openning outstanding chqs 
    $this->read_db->select(['opening_outstanding_cheque_number']);

    $this->read_db->where(['opening_outstanding_cheque_number' => $cheque_number, 'fk_office_bank_id' => $office_bank_id]);

    $opening_outstanding_chq_number_exist = $this->read_db->get('opening_outstanding_cheque')->result_array();
    
    if (count($opening_outstanding_chq_number_exist) > 0) {

      $opening_cheques = array_column($opening_outstanding_chq_number_exist, 'opening_outstanding_cheque_number');

      $invalid_cheque_numbers_for_injection = array_merge($invalid_cheque_numbers_for_injection, $opening_cheques);

    }

    // End of Addtion

    // Should not have been injected previously
    $this->read_db->select(array('cheque_injection_number'));
    $this->read_db->where(array('fk_office_bank_id' => $office_bank_id));
    $cheque_injection_obj = $this->read_db->get('cheque_injection');

    if ($cheque_injection_obj->num_rows() > 0) {
      $injected_cheques_raw = $cheque_injection_obj->result_array();
      $injected_cheques = array_column($injected_cheques_raw, 'cheque_injection_number');

      $invalid_cheque_numbers_for_injection = array_merge($invalid_cheque_numbers_for_injection, $injected_cheques);
    }

    //log_message('error', json_encode($invalid_cheque_numbers_for_injection));

    $this->load->model('cheque_book_model');

    $cancelled_cheques = $this->cheque_book_model->get_cancelled_cheques($office_bank_id);


    if (count($cancelled_cheques) > 0) {

      $invalid_cheque_numbers_for_injection = array_diff($invalid_cheque_numbers_for_injection, $cancelled_cheques);
    }

    // log_message('error', json_encode($invalid_cheque_numbers_for_injection));

    if (!in_array($cheque_number, $invalid_cheque_numbers_for_injection)) {
      $is_valid_cheque = 1;
    }

    echo $is_valid_cheque;
  }*/



  //   // Check if the injected leaf is before the first cheque book. 
  //   //This is the first cheque book first serial number for the office for a given bank

  //   $min_serial_number = $this->cheque_book_model->office_bank_start_cheque_serial_number($post['office_bank_id']);

  //   // Check id injection leaf is already in the cheque_injection table
  //   $this->read_db->where(array('fk_office_bank_id'=>$post['office_bank_id'],
  //   'cheque_injection_number'=>$post['cheque_number']));
  //   $cheque_injection_count = $this->read_db->get('cheque_injection')->num_rows();

  //   // List all cancelled cheque numbers for an office bank
  //   $cancelled_cheque_numbers = $this->cheque_book_model->cancelled_cheque_numbers($post['office_bank_id']);

  //   $used_cheque_leaves = $this->cheque_book_model->get_used_cheque_leaves($post['office_bank_id']);

  //   // Only inject if missing in the cheque injection table and is lesser than the start serial of the initial cheque book

  //   if(
  //       ( 
  //         // Cheque leaf has not been injected before
  //         $cheque_injection_count == 0 && 
  //         (
  //           (
  //             // Cheque leaf is not in the recorded cheque books and also has not been used in the voucher
  //             $min_serial_number > $post['cheque_number'] &&  
  //             !in_array($post['cheque_number'], $used_cheque_leaves) 
  //           ) 
  //           || 
  //           (
  //             // Cheque leaf is within the recorded cheque books and the leaf is present in vouchers as cancelled i.e. has both -ve and -ve number
  //             $min_serial_number <= $post['cheque_number'] && 
  //             in_array($post['cheque_number'],$cancelled_cheque_numbers) && 
  //             in_array($post['cheque_number'], $used_cheque_leaves)
  //           )
  //         )
  //       ) 

  //     ){
  //     $validate_cheque_number = true;
  //   }

  //   echo $validate_cheque_number;
  // }

  
}
