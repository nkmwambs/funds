<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */


class Attachment extends MY_Controller
{

  function __construct(){
    parent::__construct();
    $this->load->library('attachment_library');
  }

  function index(){}

  /**
   * unlink_old_files_in_filesystem
   *
   * This method is run by a cron job to delete old temps uploads
   * 
   * @return void
   */
  function unlink_old_files_in_filesystem(){

    $path = FCPATH.'uploads'.DIRECTORY_SEPARATOR.'temps'.DIRECTORY_SEPARATOR;
    if ($handle = opendir($path)) {

        while (false !== ($file = readdir($handle))) { 
            $filelastmodified = filemtime($path . $file);
            //24 hours in a day * 3600 seconds per hour
            if((time() - $filelastmodified) > $this->config->item('temp_files_deletion_limit_hours') * 3600 && $file !='..')
            {
              unlink($path . $file);
            }

        }

        closedir($handle); 
    }
  }

  static function get_menu_list(){}

}