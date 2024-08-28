<style>
    th, td {
        white-space:nowrap;
    }
</style>
<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
} ?>

<div class="row" style="margin-bottom:25px;">
    <div class="col-xs-12" style="text-align:center;">
        <?php
        extract($result);
        //print_r($result);

        // if($show_add_button && $this->user_model->check_role_has_permissions(ucfirst($this->controller),'create')){
        //     //echo add_record_button($this->controller, $has_details_table,null,$has_details_listing, $is_multi_row);
        //   }
        ?>
    </div>
</div>


<div class="row">
    <div class="col-xs-12">
        <table class="table table-striped" id="datatable">
            <thead>
                <!-- Mass Update Button and Checkbox -->
                <tr >
                    <td colspan='8'> <input type="checkbox" value="" id="select_chkbox"  >
                        <!-- <label class="form-check-label" for="select_chkbox">ALL</label> -->
                        <button class="btn btn-primary hidden" id="activate_all">Activate All</button>

                        <button class="btn btn-default hidden" id="reject_all">Reject All</button>
                    </td>

                </tr>
                <tr>

                    <th>Activate/Reject User</th>

                    <?php

                    foreach ($columns as $column) {
                        
                    ?>
                        <th ><?= get_phrase($column); ?></th>
                       
                    <?php
                    }
                    ?>
                </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
    </div>
</div>

<script>
    //$(document).ready(function(){
    var url = "<?= base_url(); ?><?= $this->controller; ?>/show_list";
    const datatable = $("#datatable").DataTable({
        dom: 'lBfrtip',
        buttons: [
            'copyHtml5',
            'excelHtml5',
            'csvHtml5',
            'pdfHtml5',
        ],
        pagingType: "full_numbers",
        // stateSave:true,
        pageLength: 10,
        order: [],
        serverSide: true,
        processing: true,
        language: {
            processing: 'Loading ...'
        },
        ajax: {
            url: url,
            type: "POST",
        }
    });
    // });

    $("#datatable_filter").html(search_box());

    //});

    function search_box() {
        return '<?= get_phrase('search'); ?>: <input type="form-control" onchange="search(this)" id="search_box" aria-controls="datatable" />';
    }

    function search(el) {
        datatable.search($(el).val()).draw();
    }

    //Show Mass Update button and check all the other checkboxes 
    var userAccountActivationArr = [];

    $(document).on('change', '#select_chkbox', function(event) {

        userAccountActivationArr = [];
        //Check all checkboxes
        var chkBoxes = $('.form-check-input');

        if ($(this).is(":checked")) {
            //Show Bulk activate/reject button CheckBoxes
            $('#activate_all').removeClass('hidden');

            $('#reject_all').removeClass('hidden');


            $.each(chkBoxes, function(i, el) {
                $(el).prop('checked', true);

                //Populate array
                let userToActivateId = $(el).prop('id');

                let id = userToActivateId.split('_')[1];

                userAccountActivationArr.push(id);

            });
        } else {
            //Hide button and unmark CheckBoxes
            $('#activate_all').addClass('hidden');

            $('#reject_all').addClass('hidden');

            $.each(chkBoxes, function(i, el) {

                $(el).prop('checked', false);

                userAccountActivationArr = [];

            });
        }

        console.log(userAccountActivationArr);

    });

    function bulkUserActivationOrRejection(bulkUrl, message) {
        //Check uncheccked values and remove from array before deleting
        var chkBoxes = $('.form-check-input');

        $.each(chkBoxes, function(i, elem) {

            if (!$(elem).is(':checked')) {

                let userToActivateId = $(elem).prop('id');

                let id = userToActivateId.split('_')[1];

                //Remove values that are diselected by unchecked chkboxes
                userAccountActivationArr = $.grep(userAccountActivationArr, function(value) {
                    return value != id;
                });

            } else {
                $(elem).closest('tr').remove();
            }

        });

        //Now Loop userAccountActivationArr as u you update user, context and department user
        if (userAccountActivationArr.length > 0) {

            var data = {};

            $.each(userAccountActivationArr, function(index, el) {

                //Pass data to either  reject or activate users
                if (bulkUrl == 'activate_new_user_account') {

                    data = {
                        userIdToActivate: el,
                    }
                } else {
                    data = {
                        rejectedUserId: el,
                    }
                }


                let url = "<?= base_url() ?>user_account_activation/" + bulkUrl;

                $.post(url, data, function(updateDataResponse) {



                });
                //Check if last index to show message
                var isLastElement = index == userAccountActivationArr.length - 1;

                if (isLastElement) {
                    alert(userAccountActivationArr.length + message);

                }

            });
        }
    }

    //Bulk activation of users
    $(document).on('click', '#activate_all', function() {


        let confirmUserActivation = confirm("Are you sure you want to activate " + userAccountActivationArr.length + " new users");

        if (confirmUserActivation) {

            bulkUserActivationOrRejection('activate_new_user_account', ' New users have been activated & can access Safina');

        } else {
            //Do nothing
        }

    });

    //Bulk rejection of users
    $(document).on('click', '#reject_all', function() {

        let confirmUserRejection = confirm("Are you sure you want to reject activating " + userAccountActivationArr.length + " new users");

        if (confirmUserRejection) {

            bulkUserActivationOrRejection('reject_activating_new_user_account', ' New users have been rejected & can not access Safina');

        } else {
            //Do nothing
        }


    });

    //Show Bulk action button if atleast individual chkbox is selected other hide

    $(document).on('click', '.form-check-input', function() {

        if ($(this).is(':checked')) {

            let userToActivateId = $(this).prop('id');

            let id = userToActivateId.split('_')[1];

            userAccountActivationArr.push(id);

        } else {

           //Get the selected ID and remove it from array
            let userToActivateId = $(this).prop('id');

            let id = userToActivateId.split('_')[1];
          
            const index_to_remove = userAccountActivationArr.indexOf(id);

            userAccountActivationArr.splice(index_to_remove, 1);
        }
        //Check if array is empty
        if (userAccountActivationArr.length != 0) {
            $('#activate_all').removeClass('hidden');
            $('#reject_all').removeClass('hidden');

        } else {
            $('#activate_all').addClass('hidden');
            $('#reject_all').addClass('hidden');

            //Uncheck the checkbox of selecting all other checkboxes
            $('#select_chkbox').prop('checked', false);
        }
        console.log(userAccountActivationArr);

    });

    //Activate User

    $(document).on('click', '.btn-success', function() {


        let confirmUserActivation = confirm("Are you sure you want to activate new user");

        if (confirmUserActivation) {
            //get the button Id property

            let idPropertValue = $(this).prop('id');

            //Get the record of idPropertValue in db which will help pull the fk_user_id from the user_activation_account table
            let recordId = idPropertValue.split('_')[1];

            let data = {
                userIdToActivate: recordId,
            }

            let url = "<?= base_url() ?>user_account_activation/activate_new_user_account/";

            $.post(url, data, function(updateDataResponse) {

                if (parseInt(updateDataResponse) == 1) {

                    alert('User Activated and Ready to use Safina');

                } else {
                    alert('User Not activated contact system administrator or developer');
                }

            });
            //Remove the row
            $(this).closest('tr').remove();

        } else {
            //do nothing
        }


    });


    //Reject activating User

    $(document).on('click', '.btn-danger', function() {

        //get the button Id property and split to get the actual id in table e.g 15

        let idPropertValue = $(this).prop('id');

        let recordId = idPropertValue.split('_')[1];

        //check if the refect resean is provided

        const rejectReasonId='#rejectreason_'+recordId;

        let rejectReason=$(rejectReasonId).val();

        let userRejectionReason='';

        //Unhide the select dropdown for reject reason
        $(rejectReasonId).removeClass('hidden');
        $(rejectReasonId).css('border','2px dotted red');

        if(rejectReason!=0){

            userRejectionReason=$(rejectReasonId+" option:selected").text();
           
        }else{
            alert('Provide the rejection reason');

            return false;
        }
 

        let userRejectActivation = confirm("Are you sure you want to reject activating new user");

        if (userRejectActivation) {


            let data = {
                rejectedUserId: recordId,
                rejectReason:userRejectionReason
            }

            let url = "<?= base_url() ?>user_account_activation/reject_activating_new_user_account/";

            $.post(url, data, function(deleteDataResponse) {

                console.log(JSON.parse(deleteDataResponse));

                if (parseInt(deleteDataResponse) == 1) {

                    alert('User Has been removed and Will Never Access Safina');

                } else {
                    alert('An Error Occurred Contact System Administrator or Developer');
                }

            });
            //Remove the row
            $(this).closest('tr').remove();

        } else {
            //Do nothing
        }

    });

    //Remove dotted lines on change of the rejectReason dropdown
    $(document).on('change','.form-control', function(){

        $(this).css('border','');

    });
</script>