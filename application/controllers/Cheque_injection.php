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
  
}
