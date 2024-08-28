<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />

<?php 
    extract($result);
?>

<div class = 'row'>
    <div class = 'col-xs-12'>
        <div class = 'form-group col-xs-4'>
            <select class = 'form-control' id = 'transaction_type' >
                <option value = ''><?=get_phrase('select_transaction_type');?></option>
                <option value = '1' selected><?=get_phrase('income');?></option>
                <option value = '2'><?=get_phrase('bank_expenses');?></option>
                <option value = '3'><?=get_phrase('cash_expenses');?></option>
                <option value = '4'><?=get_phrase('all_expenses');?></option>
            </select>
        </div>
        <div class = 'form-group col-xs-4'>
            <input class = 'form-control' id = 'date_range' type = 'text' readonly />
        </div>
        <div class = 'col-xs-1'>
            <?=get_phrase('filter_civs','Filter CIVs Only');?> <input type = 'checkbox' class = 'form-conrol' id = 'filter_civs'  />
        </div>
        <div class = 'col-xs-1'>
            <div class = 'btn btn-success' id = 'run' ><?=get_phrase('run');?></div>
        </div>
    </div>
</div>


<div class = 'row'>
    <div class = 'col-xs-12' id = 'load_table'>
        <?php echo $transaction_summary;?>
    </div>
</div>

<script>

    $('#date_range').daterangepicker({
        autoUpdateInput: false,
        locale: {
            cancelLabel: 'Clear'
        },
        ranges: {
           'Today': [moment(), moment()],
           'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
           'Last 7 Days': [moment().subtract(6, 'days'), moment()],
           'Last 30 Days': [moment().subtract(29, 'days'), moment()],
           'This Month': [moment().startOf('month'), moment().endOf('month')],
           'This Year': [moment().startOf('year'), moment().endOf('year')],
           'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
    });

    $('#date_range').on('apply.daterangepicker', function(ev, picker) {
      $(this).val(picker.startDate.format('YYYY-MM-DD') + ' -> ' + picker.endDate.format('YYYY-MM-DD'));
    });

    $("#run").on('click', function () {

        const transaction_type = $("#transaction_type").val()
        const date_range = $('#date_range').val()
        const filter_civs = $('#filter_civs').is(':checked') ? 1 : 0
        const data = {transaction_type,date_range,filter_civs }
        const url = '<?=base_url();?>transactions_summary_report/refresh_report'
        
        $.post(url,data, function (response) {
            $('#load_table').html(response)
        })

    })
    
</script>