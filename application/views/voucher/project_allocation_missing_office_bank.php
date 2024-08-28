<?php if(!empty($project_allocations)){ ?>


<div class='row'>
    <div class="col-xs-12">
        <table class='table table-bordered' style="background-color: red;">
            <thead>
                <tr>
                    <th colspan="4" style="text-align: center;font-weight:bolder;"><?=get_phrase('unlinked_project_allocations_to_office_bank');?></th>
                </tr>
                <tr>
                    <th><?=get_phrase('project_name');?></th>
                    <th><?=get_phrase('project_allocation_name');?></th>
                    <th><?=get_phrase('office_name');?></th>
                    <th><?=get_phrase('link_to_office_bank');?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($project_allocations as $project_allocation){?>
                    <tr>
                        <td><?=$project_allocation['project_name'];?></td>
                        <td><?=$project_allocation['project_allocation_name'];?></td>
                        <td><?=$project_allocation['office_name'];?></td>
                        <td><?=office_bank_select($project_allocation['office_id'],$project_allocation['project_allocation_id'])?></td>
                    </tr>
                <?php }?>
            </tbody>
        </table>
    </div>
</div>

<hr/>

<?php } ?>

<script>
    $(document).on('change','.change_office_bank',function(){
        var row = $(this).closest('tr');
        var project_allocation_id = $(this).data('project_allocation_id');
        var data ={'fk_project_allocation_id':project_allocation_id,'fk_office_bank_id':$(this).val()};
        var url = "<?=base_url();?>Office_bank_project_allocation/insert_office_bank_project_allocation";

        var cnf = confirm('Are you sure you want to create this association');

        if(!cnf){
            alert('Process aborted');
            return false;
        }

        $.post(url,data,function(response){
            console.log(response);
            row.remove();
            //return false;
        });
    });
</script>