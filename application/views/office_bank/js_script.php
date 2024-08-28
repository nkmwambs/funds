<script>
    $(document).ready(function(){

        const action = '<?=$this->action;?>'

        if(action == 'edit'){
            $('#fk_office_id').prop('readonly', 'readonly')
        }


        $('#fk_office_id').on('change', function() {
            const office_id = $('#fk_office_id').val();
            const url = '<?=base_url();?>office_bank/income_account_requiring_allocation/' + office_id;
            const count_active_office_banks_url = '<?=base_url();?>office_bank/count_active_office_banks/' + office_id


            $.get(url, function (response) {

                const response_obj = JSON.parse(response)

                if(response_obj.unallocated_income_account.length > 0){
                    $message_string = 'The following income accounts are not associated to project for this office.';
                    $message_string += 'You will not be able to save this new office bank until this issue is fixed.\n\n';

                    $.each(response_obj.unallocated_income_account, function (i, elem) {
                        $message_string +=  i+1 +'. ' + elem.income_account_name + '\n';
                    });


                    alert($message_string);

                    $(".save, .save_new").addClass('disabled');
                }else{
                    $(".save, .save_new").removeClass('disabled');

                    $.get(count_active_office_banks_url, function (count_active_office_banks) {
                        // alert(count_active_office_banks)
                        if(count_active_office_banks > 0){
                            $('#office_bank_conditions').removeClass('hidden');
                            $('.save, .save_new').addClass('disabled')
                            alert('<?=get_phrase('notify_user_to_refer_office_bank_condition','Please confirm by checking the condition boxes that will appear above this form. These conditions are required to be met before you go ahead with creating the office bank. Click "Ok" to see the conditions');?>');
                        }
                    })
                }
            })

        });


        $('.office_bank_checklist_items').change(function() {
            const conditions_to_check = 6
            const allChecked = $('.office_bank_checklist_items:checked').length === conditions_to_check;

            if (allChecked) {
                $('.save, .save_new').removeClass('disabled');
            } else {
                $('.save, .save_new').addClass('disabled');
            }
        });

    });
    //Check if the office_bank_chequebook_size is empty;
   $('.save, .save_new').on('click', function(){

    const chqbk_size_elem=$('#office_bank_chequebook_size');

    if(chqbk_size_elem.val()==''){
        
        alert('<?=get_phrase('chqbooksize', 'Please Select Standard Bank Chequebook Number Of Leaves');?>');

        $(chqbk_size_elem).css("border", "1px solid red").select2();

        return false;
    }
    
   });

</script>