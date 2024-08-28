<script>
$(document).ready(function(){
    removeUnwantedFieldsOnStatusEdit()
});

function removeUnwantedFieldsOnStatusEdit(){
    const action = '<?=$this->action;?>';
    if(action == 'edit'){
        const url = "<?=base_url();?>status/check_if_status_is_straight_jump/<?=hash_id($this->id,'decode');?>";
        
        $("#status_approval_sequence").parent().parent().parent().remove();

        $.get(url,function(response){

            const obj = JSON.parse(response)
            if(obj.is_straight_jump == 0){
                $("#status_decline_button_label").parent().parent().parent().remove();
            }

            if(obj.final_status){
                $("#status_signatory_label").parent().parent().parent().remove();
                $("#status_button_label").parent().parent().parent().remove();
            }
        });
    }
}
</script>