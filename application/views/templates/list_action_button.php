<div class="dropdown">
    <button class="btn btn-default dropdown-toggle" type="button" id="menu1" data-toggle="dropdown">
        <?=get_phrase('action');?>
             <span class="caret"></span></button>
                  <ul class="dropdown-menu" role="menu" aria-labelledby="menu1">
                    <?php
                    $action_labels = $this->grants->action_labels($this->controller,$primary_key);
                    if(isset($action_labels['show_label_as_button']) && $action_labels['show_label_as_button']){  
                      if($this->user_model->check_role_has_permissions(ucfirst($this->controller),'update')){ ?>
                      <li><?=list_table_edit_action($this->controller,$primary_key);?></li>
                      <li class="divider"></li>
                      <?php }?>
                      <?php if($this->user_model->check_role_has_permissions(ucfirst($this->controller),'delete')){ ?>
                      <li><?=list_table_delete_action($this->controller,$primary_key);?></li>
                    <?php 
                      }
                      }else{
                        echo "<li><a href='#'>".get_phrase('no_action')."</a></li>";
                      }
                    ?>

                    <?php if(
                        !$this->user_model->check_role_has_permissions(ucfirst($this->controller),'update') && 
                        !$this->user_model->check_role_has_permissions(ucfirst($this->controller),'delete')

                    ){ 
                        echo "<li><a href='#'>".get_phrase('no_action')."</a></li>";
                    }?>

                  </ul>
                </div>