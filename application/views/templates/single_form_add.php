<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

  //print_r($this->user_model->check_if_user_has_office_data_view_edit_permission('cheque_book'));
?>

<?php
  extract($result);

  $form = $this->element;

  $table = $form->create_single_form_add($this->controller, $fields,'add_form');
  echo $form->add_form('Add '.$this->controller,$table);

