<div class='row'>
  <div class='col-xs-12'>
    <div class="dropdown">
      <button class="btn btn-default dropdown-toggle" type="button" id="" data-toggle="dropdown">
        <?=get_phrase('action');?>
          <span class="caret"></span></button>
              <ul class="dropdown-menu" role="menu" aria-labelledby="menu1">
                <?php 
                  if(isset($action_labels['show_label_as_button']) && $action_labels['show_label_as_button']){
                      if($this->user_model->check_role_has_permissions(ucfirst($table),'update'))
                        { 
                ?>
                          <li><?=list_table_edit_action($table,$primary_key);?></li>
                          
                      <li class="divider"></li>
                      
                      <?php 
                          }
                           
                          if($this->user_model->check_role_has_permissions(ucfirst($table),'delete'))
                          { 
                        ?>
                          <li><?=list_table_delete_action($table,$primary_key);?></li>
                          <li class="divider"></li>
                          <?php 
                            }
                          
                           if($is_approveable_item) {

                             if( isset($action_labels['show_label_as_button']) && $action_labels['show_label_as_button']){
                              //.$action_labels['button_label']."</a></li>".Widget_base::load('button',$action_labels['button_label'],$table.'/approve/'.hash_id($primary_key,'encode'))
                              echo "<li><a href='".base_url().$table."/approve/".hash_id($primary_key,'encode')."'>".$action_labels['button_label']."</a></li>";
                              
                              if($action_labels['show_decline_button']){
                                echo Widget_base::load('button',get_phrase('decline'),$table.'/decline/'.hash_id($primary_key,'encode'));
                          
                              }

                              }


                           }
                          }else{
                          ?>
                            <!-- <li class="divider"></li> -->
                            <li><a href="#"><?=get_phrase('no_action');?></a></li>
                          
                          <?php
                          }
                          ?>
                       </ul>
                     </div>
  </div>
</div>
