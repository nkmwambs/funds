<style>
    .center{
        text-align:center;
    }
    .notification{
        color:red;
        font-weight:bold;
    }
</style>

<?php
    extract($result);

    // print_r($target_offices);
?>

<div class="row">
    <div class="col-xs-12">
        <div class="panel panel-default" data-collapsed="0">
                <div class="panel-heading">
                    <div class="panel-title">
                        <i class="entypo-plus-circled"></i>
                        <?php echo get_phrase('replicate_opening_balances'); ?>
                    </div>
                </div>

                <div class="panel-body" style="max-width:50; overflow: auto;">
                    <?php echo form_open("", array('id' => 'replicate_balance', 'class' => 'form-horizontal form-groups-bordered validate', 'enctype' => 'multipart/form-data')); ?>

                    <div class='form-group'>
                        <div class='col-xs-12 center'>
                            <div class='btn btn-default btn-reset'><?= get_phrase('reset'); ?></div>
                            <div class='btn btn-default btn-save'><?= get_phrase('save'); ?></div>
                        </div>
                    </div>

                    <div class='form-group'>
                        <div class='col-xs-12 center notification'>
                            <div>Ensure that target/destination offices have a default bank account in order to have them appear in the dropdown</div>
                        </div>
                    </div>

                    <div class='form-group'>
                        <label for="" class="control-label col-xs-4"><?=get_phrase('origin_opening_balance_name');?></label>
                        <div class="col-xs-8">
                            <select name="opening_balance_id" id="opening_balance_id" class="form-control">
                                <option value="<?=$origin_office['system_opening_balance_id'];?>"><?=$origin_office['system_opening_balance_name'];?></option>
                            </select>
                        </div>
                    </div>

                    <div class='form-group'>
                        <label for="" class="control-label col-xs-4"><?=get_phrase('origin_office');?></label>
                        <div class="col-xs-8">
                            <select name="origin_office" id="origin_office" class="form-control">
                                <option value="<?=$origin_office['office_id'];?>"><?=$origin_office['office_name'];?></option>
                            </select>
                        </div>
                    </div>

                    <div class='form-group'>
                        <label for="" class="control-label col-xs-4"><?=get_phrase('target_office');?></label>
                        <div class="col-xs-8">
                            <select name="target_offices[]" id="target_office" class="form-control select2" multiple>

                                <option value=''><?=get_phrase('select_target_office');?></option>
                                
                                <?php foreach($target_offices as $target_office){?>
                                    <option value="<?=$target_office['office_id'];?>"><?=$target_office['office_name'];?></option>
                                <?php }?>
                            
                            </select>
                        </div>
                    </div>
                    

                    <div class='form-group'>
                        <div class='col-xs-12 center'>
                            <div class='btn btn-default btn-reset'><?= get_phrase('reset'); ?></div>
                            <div class='btn btn-default btn-save'><?= get_phrase('save'); ?></div>
                        </div>
                    </div>
                     
            </div>
    </div>
</div>

<script>
    $(".btn-save").on('click',function(){
 
        const data = $("#replicate_balance").serializeArray();
        const url = "<?=base_url();?>replicate_opening_balances/create_replication";

        $.post(url, data, function (response) {
            if(response){
                alert("<?=get_phrase('replication_successful');?>");

                location.href = document.referrer    
            }else{
                alert("<?=get_phrase('replication_failed');?>");
            }
        });
    });
</script>