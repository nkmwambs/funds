<?php 

extract($result);

if($this->user_model->check_role_has_permissions(ucfirst('language_phrase'), 'update')){
    
    if($this->user_model->check_role_has_permissions(ucfirst($this->controller),'update'))
                {
                     echo Widget_base::load('button',get_phrase('edit'),$this->controller.'/edit/'.$this->id);
                }
  
                if($this->user_model->check_role_has_permissions(ucfirst($this->controller),'delete'))
                {
                    echo Widget_base::load('button',get_phrase('delete'),$this->controller.'/delete/'.$this->id);
                }


    $path = $this->session->user_account_system.DIRECTORY_SEPARATOR.$language['language_code'].'_lang';

    echo $this->grants->config_list('language', $path,'lang');
    
}else{
    include "view_language_phrases.php";
}
