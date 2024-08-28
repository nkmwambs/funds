<script>
    let income_account = $('#fk_income_account_id')
    let office_bank = $('#fk_office_bank_id')
    let project = $('#fk_project_id')

    const income_account_form_group = income_account.closest('.form-group')
    const office_bank_form_group = office_bank.closest('.form-group')

    const action = '<?=$this->action;?>';

    if(action == 'single_form_add'){
        income_account_form_group.hide()
        office_bank_form_group.hide()
    }else if(action == 'edit'){
        $("#fk_project_id, #fk_income_account_id, #fk_office_bank_id, #fk_system_opening_balance_id").prop('readonly','readonly');
        // $("#fk_income_account_id").prop('readonly','readonly');
        // $("#fk_office_bank_id").prop('readonly','readonly');
    }

    project.on('change', unhide_income_account)

    function unhide_income_account  () {
        const project_id = $(this).val()
        const url = "<?=base_url();?>project_income_account/get_income_accounts_by_project_id/" + project_id
       
        income_account.find('option').remove()
        income_account.html('<option value = "">Select Income Account</option>')

        $.get(url, function (response) {
            const resp_obj = JSON.parse(response)

            income_account_form_group.show()
            
            let options = '';

            $.each(resp_obj, function (i, el) {
                options += `<option value = '${el.income_account_id}'>${el.income_account_name}<option>`
            })

            income_account.append(options);

            
        })
    }

    income_account.on('click', unhide_office_bank)
    
    function unhide_office_bank () {
        project.find('option').not(":selected").remove()

        office_bank_form_group.show()
    }

</script>