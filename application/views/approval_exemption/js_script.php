<script>
    let action = '<?=$this->action;?>';

    if(action == 'edit'){
        $('#fk_office_id').prop('readonly','readonly')
        $('#approval_exemption_name').prop('readonly','readonly')
    }
</script>