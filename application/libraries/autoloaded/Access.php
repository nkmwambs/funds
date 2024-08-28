<?php
/**
* The grants management system is a framework developed by Compassion Africa Regional Development team
* to help FCPs manage their finances. It an open framework that be easily be adopted by any grant managing
* organization.
*
* @author Nicodemus Karisa
* @package Grants Management System
* @copyright Compassion International Kenya
* @license https://compassion-africa.org/lisences.html
*
*/

defined('BASEPATH') OR exit('No direct script access allowed');

/**
* This class defines the access capability of users
*
* @package		Grants Management System
* @category	User Access
* @author		Compassion International Kenya
*
*/


class Access{
  /**
  * Variable to hold CodeIgniter instance
  * @var object
  */
  private $CI;

  /**
  * Class constructor
  * @return void
  */
  function __construct(){
    $this->CI =& get_instance();
    //$this->CI->load->database();
  }

  

}
