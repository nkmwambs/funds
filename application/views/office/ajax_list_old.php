

<div class="row">
    <div class="col-xs-12">
        <?php 
           // print_r($office_order);
        ?>

        <table class="table table-striped">
            <thead>
                <th><?=get_phrase('action');?></th>
                <th><?=get_phrase('office_code');?></th>
                <th><?=get_phrase('office_name');?></th>
                <th><?=get_phrase('office_start_date');?></th>
                <th><?=get_phrase('office_context');?></th>
                <th><?=get_phrase('reporting_office');?></th>
            </thead>
            <tbody>
                <?php foreach($office_order as $office){?>
                    <tr>
                        <th>
                           <?php  //echo $office['context_definition_id'] .' - '. $user_context_definition_id;?>
                            <?php if($office['context_definition_id'] > 1){?>
                                <div data-context_id = "<?=$office['context_id'];?>" data-context_definition_id = "<?=$office['context_definition_id'];?>" class="btn btn-default btn-icon expand"><i class="fa fa-arrow-down"></i> <?=get_phrase('expand');?></div>
                            <?php }?>

                            <?php if($office['context_definition_id'] < $user_context_definition_id - 1){?>
                                <div data-up_context_id = "<?=$office['context_id'];?>" data-up_context_definition_id = "<?=$office['context_definition_id'];?>" class="btn btn-default btn-icon narrow"><i class="fa fa-arrow-up"></i> <?=get_phrase('collapse');?></div>
                            <?php }?> 

                            <a target="__blank" href="<?=base_url();?>office/view/<?=hash_id($office['office_id'],'encode');?>" class="btn btn-default btn-icon"><i class="fa fa-eye"></i> <?=get_phrase('view');?></a>
                            
                            <?php if($this->user_model->check_role_has_permissions(ucfirst($this->controller),'update')){?>
                                <a target="__blank" href="<?=base_url();?>office/edit/<?=hash_id($office['office_id'],'encode');?>" class="btn btn-default btn-icon"><i class="fa fa-pencil"></i> <?=get_phrase('edit');?></a>
                            <?php }?>
                        
                        </th>
                        <td><?=$office['office_code'];?></td>
                        <td><?=$office['office_name'];?></td>
                        <td><?=$office['office_start_date'];?></td>
                        <td><?=ucfirst($office['context_definition_name']);?></td>
                        <td><?=$office['reporting_context_name'];?></td>
                    </tr>
                <?php }?>
            </tbody>
        </table>
    </div>
</div>

