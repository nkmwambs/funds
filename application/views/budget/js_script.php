<script>
    // An alternate redirection instead of referrer address
    // const alt_referrer = '<?=base_url();?>budget_limit/single_form_add';

    // Remove the select2 class for budget year and budget tag since they don't populate as expected with options dynamically
    // Also remove the default options
    // $("#budget_year").removeClass("select2").html('');
    // $("#fk_budget_tag_id").removeClass("select2").html('');

    function onchange_fk_funder_id(elem){
        if( $('#fk_office_id').val() > 0){
            list_valid_budget_years_for_office()
        }
    }

    function onchange_fk_office_id(elem){
        list_valid_budget_years_for_office()
    }

    function list_valid_budget_years_for_office(){
        const office_id = $('#fk_office_id').val();
        const funder_id = $('#fk_funder_id').val();

        const year_url = "<?=base_url();?>budget/list_valid_budget_years_for_office"
        const accounts_url = "<?=base_url();?>budget/list_budgetable_income_account/"

        const data = {
            office_id,
            funder_id
        }
        
        // console.log(funder_id, office_id)

        $("#budget_year").html('');
        $("#fk_budget_tag_id").html('');

        if(office_id && !funder_id){
            alert('<?=get_phrase('select_a_valid_funder',"Please select a valid funder");?>')
            return false;
        }
       
        $.post(year_url,data,function(fys){
            let options = "<option value=''><?=get_phrase('select_financial_year');?></option>";
            const fys_object = JSON.parse(fys);

            $.each(fys_object, function(i, fy){
                options += "<option value='"+fy+"'>FY"+fy+"</option>";
            });

            $("#budget_year").html(options);
        });

        $.post(accounts_url, data, function (resp) {
            const obj = JSON.parse(resp)
            // alert(resp)
            let options = '<option value = "">Select Income Account</option>'
            $.each(obj, function (i,el) {
                options += '<option value = "' + el.income_account_id + '">' + el.income_account_name + '</option>'
            })
            
            $(".fk_income_account_id").html(options)

            
        })
    }


    $("#budget_year").on('change',function(){
        const office_id = $("#fk_office_id").val();
        const funder_id = $("#fk_office_id").val();
        const budget_year = $(this).val();
        const data = {
            office_id,
            budget_year,
            funder_id
        }

        const url = "<?=base_url();?>budget/get_office_budget_tags";

        $.post(url,data,function(tags){
            let options = "<option value=''><?=get_phrase('select_budget_tag');?></option>";

            const tags_object = JSON.parse(tags);

            $.each(tags_object, function(i, tag_name){
                options += "<option value='"+i+"'>"+tag_name+"</option>";
            });

            $("#fk_budget_tag_id").html(options);
        });
    });

</script>