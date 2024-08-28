<script>

$(document).ready(function () {
    
    const url = '<?=base_url();?>opening_outstanding_cheque/get_office_start_date/<?=hash_id($this->id,'decode');?>'
    
    $('.datepicker').datepicker('remove');

    $.get(url, function (response){
        const controlled_dates = JSON.parse(response);
        
        $('#opening_outstanding_cheque_date').datepicker({
            format: 'yyyy-mm-dd',
            startDate: controlled_dates.start_date,
            endDate: controlled_dates.end_date
        });
    })
})

</script>