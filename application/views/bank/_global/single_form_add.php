<?php
extract($result);

$form = $this->element;

$table = $form->create_single_form_add($this->controller, $fields,'add_bank');
echo $form->add_form('Add '.$this->controller,$table);