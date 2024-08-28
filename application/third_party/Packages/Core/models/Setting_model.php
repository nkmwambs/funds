<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Setting_model extends MY_Model
{
  public $table = 'setting_id'; // you MUST mention the table name
  public $primary_key = 'setting_id'; // you MUST mention the primary key
  public $fillable = array(); // If you want, you can set an array with the fields that can be filled by insert/update
  public $protected = array(); // ...Or you can set an array with the fields that cannot be filled by insert/update

  function __construct(){
    parent::__construct();
    $this->load->database();

  }

  function index(){

  }

  function intialize_table(){

    $settings = [
      [
        'type'=>'system_name','description'=>'Grants Management System','setting_created_date'=>date('Y-m-d'),'setting_created_by'=>1,'setting_last_modified_by'=>1
      ],
      [
        'type'=>'system_title','description'=>'Grants Management System','setting_created_date'=>date('Y-m-d'),'setting_created_by'=>1,'setting_last_modified_by'=>1
      ],
      [
        'type'=>'address','description'=>'1945 Nairobi','setting_created_date'=>date('Y-m-d'),'setting_created_by'=>1,'setting_last_modified_by'=>1
      ],
      [
        'type'=>'phone','description'=>'+254711808071','setting_created_date'=>date('Y-m-d'),'setting_created_by'=>1,'setting_last_modified_by'=>1
      ],
      [
        'type'=>'system_email','description'=>'nkmwambs@gmail.com','setting_created_date'=>date('Y-m-d'),'setting_created_by'=>1,'setting_last_modified_by'=>1
      ],
      [
        'type'=>'language','description'=>1,'setting_created_date'=>date('Y-m-d'),'setting_created_by'=>1,'setting_last_modified_by'=>1
      ],
      [
        'type'=>'text_align','description'=>'left-to-right','setting_created_date'=>date('Y-m-d'),'setting_created_by'=>1,'setting_last_modified_by'=>1
      ],
      [
        'type'=>'skin_colour','description'=>'blue','setting_created_date'=>date('Y-m-d'),'setting_created_by'=>1,'setting_last_modified_by'=>1
      ],
      [
        'type'=>'system_setup_completed','description'=>0,'setting_created_date'=>date('Y-m-d'),'setting_created_by'=>1,'setting_last_modified_by'=>1
      ],
      [
        'type'=>'setup_password','description'=>md5('@Compassion123'),'setting_created_date'=>date('Y-m-d'),'setting_created_by'=>1,'setting_last_modified_by'=>1
      ],
      [
        'type'=>'base_currency_code','description'=>'1','setting_created_date'=>date('Y-m-d'),'setting_created_by'=>1,'setting_last_modified_by'=>1
      ],
      [
        'type'=>'environment','description'=>'production','setting_created_date'=>date('Y-m-d'),'setting_created_by'=>1,'setting_last_modified_by'=>1
      ],

    ];

    $reset_auto_increment = "ALTER TABLE setting AUTO_INCREMENT = 1";
    $this->write_db->query($reset_auto_increment);

    $this->write_db->insert_batch('setting',$settings);

    //return $this->write_db->insert();
  }

}
