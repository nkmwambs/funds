<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

interface CrudModelInterface {

  //public function index();

  //public function create();

  //public function update();

  public function delete($id = null);

  public function list();

  public function view();

  // public function single_form_add();
  //
  // public function multi_form_add();

}
