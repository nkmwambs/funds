<script>
   $("#funder").on('change', function() {
        const funder_id = $(this).val();
        let office_elem = $("#office")

        // if(funder_id == ""){
            resetVoucher(false);
            // return false;
        // }

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
</script>