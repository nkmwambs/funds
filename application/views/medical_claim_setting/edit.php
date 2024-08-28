<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

extract($result);

// print_r($medical_setting_for_edit);
// echo '<br>';
// echo '<br>';
// print_r($admin_settings);
// echo '<br>';
// echo '<br>';
// print_r($account_systems);
?>

<div class="row">
    <div class="col-xs-12">
        <?= Widget_base::load('comment'); ?>
    </div>
</div>
<div class='row'>
    <div class="col-xs-12">
        <div class="panel panel-default" data-collapsed="0">
            <div class="panel-heading">
                <div class="panel-title">
                    <i class="entypo-plus-circled"></i>
                    <?php echo get_phrase('edit_medical_claim_record'); ?>
                </div>
            </div>

            <!-- Form -->

            <div class="panel-body" style="max-width:50; overflow: auto;">

                <?php echo form_open("", array('id' => 'frm_add_new_claim', 'class' => 'form-horizontal form-groups-bordered validate', 'enctype' => 'multipart/form-data')); ?>

                <div class='col-xs-12 '>
                    <!-- medical_claim_setting_id -->
                    <input class='form-control required hidden' type="text"
                        value="<?=$medical_setting_for_edit['medical_claim_setting_id'];?>"
                        name='medical_claim_setting_id' id="medical_claim_setting_id" />
                    <!-- medical_claim_setting_name -->
                    <label class='col-xs-2 control-label'><?=get_phrase('medical_claim_setting_name');?></label>
                    <div class='col-xs-4'>
                        <input class='form-control required' type="text"
                            value="<?=$medical_setting_for_edit['medical_claim_setting_name'];?>"
                            name='medical_claim_setting_name' id="medical_claim_setting_name" />
                    </div>

                    <!-- Medical_claim_setting_type -->
                    <label class='col-xs-2 control-label'><?=get_phrase('medical_claim_setting_type');?></label>
                    <div class='col-xs-4'>

                        <select class='form-control select2 required' name='medical_claim_setting_type'
                            id='medical_claim_setting_type_id'>
                            <option value="<?= $medical_setting_for_edit['fk_medical_claim_admin_setting_id']?>">
                                <?= $medical_setting_for_edit['medical_claim_admin_setting_name'] ?></option>

                            <?php 
                        foreach($admin_settings as $key=> $admin_setting){
                            if($medical_setting_for_edit['fk_medical_claim_admin_setting_id']!=$key){ ?>

                            <option value="<?= $key?>"><?= $admin_setting;?></option>
                            <?php }
                        }
                        ?>
                        </select>
                    </div>


                </div>
                <div>&nbsp;</div>
                <div class='col-xs-12 '>

                    <!-- Medical_claim_setting_value -->
                    <label class='col-xs-2 control-label'><?=get_phrase('medical_claim_setting_value');?></label>
                    <div class='col-xs-4'>
                        <input class='form-control required' type="text"
                            value="<?= $medical_setting_for_edit['medical_claim_setting_value'];?>"
                            name='medical_claim_setting_value' id="medical_claim_setting_value" />
                    </div>

                    <!-- Account_system_id -->
                    <label class='col-xs-2 control-label'><?=get_phrase('account_system');?></label>
                    <div class='col-xs-4'>

                        <select class='form-control select2 required' name='account_system' id='fk_account_system_id'>
                            <option value="<?=$medical_setting_for_edit['fk_account_system_id']?>">
                                <?=$medical_setting_for_edit['account_system_name']?></option>
                            <?php 
                       if($this->session->system_admin){

                        foreach($account_systems as $id=>$account_system){

                            if($medical_setting_for_edit['fk_account_system_id']!=$id){?>
                            <option value="<?=$id?>"><?=$account_system;?></option>
                            <?php }
                        }
                      }
                       ?>
                        </select>


                    </div>


                </div>

                <div id="message" class='col-xs-12 hidden'><h4 style='color:red;'><?=get_phrase('some_fields_are_empty_with_no_data');?></h4></div>
                <!-- Save changes button -->
                <div class='col-xs-12'>
                    <div class='form-group'>

                        <div class='col-xs-12' style='text-align:center;'>
                            <button id='btn-save-changes'
                                class='btn btn-deafult'><?=get_phrase('save_changes');?></button>
                            <button id='btn-cancel' class='btn btn-default'><?=get_phrase('cancel');?></button>
                        </div>


                    </div>
                </div>


            </div>

            </form>

        </div>
    </div>
</div>

</div>

<!-- SCRIPTS -->

<script>
//Cancel Edit

$('#btn-cancel').on('click', function(ev) {
    //alert('test');
    //Get the edit Id from URL using Javascript
    let str = window.location.href;
    str = str.split("/")

    let medical_setting_hashed_id = str[str.length - 1];
    var redirect = '<?= base_url(); ?>medical_claim_setting/view/' + medical_setting_hashed_id;
    window.location.replace(redirect);

    ev.preventDefault();

});

//Save changes
$('#btn-save-changes').on('click', function() {

    //validation of form input

    if (validate_form()) {

        $('#message').removeClass('hidden');
        return false;
    }else{

    }


    //Post data
    var url = '<?=base_url();?>Medical_claim_setting/edit_medical_claim_setting_record';

    var data = {
        medical_claim_setting_id: $('#medical_claim_setting_id').val(),
        medical_claim_setting_name: $('#medical_claim_setting_name').val(),
        // fk_medical_claim_admin_setting_id:$('#fk_medical_claim_admin_setting_id').val(),
        medical_claim_setting_type_id: $('#medical_claim_setting_type_id').val(),
        medical_claim_setting_value: $('#medical_claim_setting_value').val(),
        fk_account_system_id: $('#fk_account_system_id').val()

    };

    $.post(url, data, function(response) {
        //Redirect lines of code
        var str = window.location.href;

        str = str.split("/")

        let medical_hashed_id = str[str.length - 1];

        var redirect = '<?= base_url(); ?>medical_claim_setting/view/' + medical_hashed_id;

        //alert(response);
        if (response == 1) {
            alert('<?=get_phrase('medical_claim_setting_updated');?>');
            window.location.replace(redirect);


        } else {
            alert('<?=get_phrase('update_failed');?>');

            window.location.replace(redirect);

        }

    });
});

function validate_form() {
    var error = false;

    let claim_setting_name=$('#medical_claim_setting_name');

    let claim_setting_value=$('#medical_claim_setting_value');

    if (claim_setting_name.val() == '') {

        claim_setting_name.css('border-color','red');
        error = true;

    }
    if(claim_setting_value.val() == ''){
        claim_setting_value.css('border-color','red');
        error=true;

    }

    return error;
}
</script>