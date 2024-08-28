<?php

/**
  *This class computes the output of the list action pages. It tries to check if the feature library has
  * any list of columns set to be used by the SQL select portion. If it misses this, the class makes a step
  * of using all the fields of the selectd tables but escapes/ unsets the created_by, last_modified_by and 
  * deleted_at columns.
  * 
  * The class checks if the feature model has defined a result query if not it used the internal run_query 
  * grants model method to get the results.
  * 
  * Finally the query results are used to populate the list_output return which is packs an array to be 
  * dispatched to the load method (See in Output_template class) to MY_Controller
  *
  * @author Nicodemus Karisa
  * @package Grants Management System
  * @copyright Compassion International Kenya
  * @license https://compassion-africa.org/lisences.html
  *
  */

defined('BASEPATH') OR exit('No direct script access allowed');

  /**
   * Getting the path of the current file
   */
  $path_parts = pathinfo(__FILE__);

/**
  * This class computes the output of the list action pages. It tries to check if the feature library has
  * any list of columns set to be used by the SQL select portion. If it misses this, the class makes a step
  * of using all the fields of the selectd tables but escapes/ unsets the created_by, last_modified_by and 
  * deleted_at columns.
  * 
  * The class checks if the feature model has defined a result query if not it used the internal run_query 
  * grants model method to get the results.
  * 
  * Finally the query results are used to populate the list_output return which is packs an array to be 
  * dispatched to the load method (See in Output_template class) to MY_Controller
  *
  * @author Nicodemus Karisa
  * @package Grants Management System
  * @copyright Compassion International Kenya
  * @license https://compassion-africa.org/licences.html
  *
  */

class Multi_row_add_output extends Output_template{
 
  /**
   * __construct
   * 
   * Class constructor
   * 
   * @return void
   */
  function __construct(){
      parent::__construct();
  }

  function _output(){
    $result = $this->CI->grants->multi_row_add_output();

    return $result;
  }
}

require_once(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'create_instance.php');