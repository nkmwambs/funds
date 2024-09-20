<script>
   $("#funder").on('change', function() {
        const funder_id = $(this).val();
        let office_elem = $("#office")

        resetVoucher(false);

        $.ajax({
            url: "<?= base_url();?>/voucher/user_transacting_offices",
            type: "POST",
            success: function(response) {
                let options = "<option value=''><?= get_phrase('select_office'); ?></option>"
                $.each(response, function(index, elem){
                    options += "<option value='"+elem.office_id+"'>"+elem.office_name+"</option>"
                })
                office_elem.html(options)
            }
        });
   });

//    $(document).on('change','.form-control', function(){
//         const form_group = $(".form-group")

//         form_group.each(function(index, element){
//             // check if the form group has at least one visible child element 
//             let has_visible_children = $(element).find(":visible").length > 0
//             if(!has_visible_children){
//                 $(element).addClass('hidden')
//             }else{
//                 $(element).removeClass('hidden') 
//             }
//         })
//    })
</script>