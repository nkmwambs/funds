<!-- Include jQuery UI library -->
<!-- <script src="https://code.jquery.com/ui/1.13.0/jquery-ui.min.js"></script> -->
<!-- Include jQuery UI CSS -->
<!-- <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.0/themes/smoothness/jquery-ui.css"> -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.10.0/css/bootstrap-datepicker.min.css" integrity="sha512-34s5cpvaNG3BknEWSuOncX28vz97bRI59UnVtEEpFX536A7BtZSJHsDyFoCl8S7Dt2TPzcrCEoHBGeM4SUBDBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.10.0/js/bootstrap-datepicker.min.js" integrity="sha512-LsnSViqQyaXpD4mBBdRYeP6sRwJiJveh2ZIbW41EBrNmKxgr/LFZIiWT6yr+nycvhvauz8c2nYMhrP80YhG7Cw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<style>
    .hidden_elem{
        display:none;
    }

    /* CSS for selected row */
    tr.selected {
        background-color: #f5f5f5;
    }

    /* CSS for highlighted column */
    td.highlight {
        background-color: #ffc107;
    }

    /* Freeze table headers */
    .dataTables_scrollHead {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        background-color: #fff;
        overflow: hidden;
        z-index: 10;
    }

    /* Adjust table layout */
    table#datatable {
        width: 100%;
    }
    table#datatable thead th {
        white-space: nowrap; /* Prevent text wrapping in header cells */
    }


</style>

<?php 
    $selected_accounting_system = 0;
    extract($result);
?>

<div class = 'row'>
    <div class = 'form-group col-xs-3'>
            <input class = 'form-control datepicker' value="<?=$month;?>" id = 'date_range' type = 'text' onkeydown="return false;" />
    </div>

    <div class = 'form-group col-xs-3 <?=!$this->session->system_admin ? 'hidden_elem' : '';?>'>
        <select id= "account_system_id" class="form-control">
            <option value=""><?=get_phrase('select_a_national_office');?></option>
            <?php foreach($accounting_system as $accounting_system_id => $accounting_system_name){?>
            <option value="<?=$accounting_system_id;?>"><?=$accounting_system_name;?></option>
            <?php }?>
        </select>
    </div>

        <div class = 'col-xs-2'>
            <select class = 'form-control select2' id = 'report_category'>
                <option value="">Select report category</option>
                <option value="fund_balance">Fund Balance Report</option>
                <option value="project_balance">Project Balance Report</option>
            </select>
        </div>

        <div class = 'col-xs-2'>
            <select class = 'form-control select2' id = 'accounts'>
                <option value = "">Select account</option>
            </select>
        </div>

</div>

<div id = "warning_holder" class="row <?=!$this->session->system_admin ? 'hidden_elem' : '';?>">
    <div class="well col-xs-12" style="text-align:center;font-weight:bold;color:red;"><?=get_phrase('national_office_not_selected');?></div>
</div>

<div id = "month_label_holder" class = "row <?=$this->session->system_admin ? 'hidden_elem' : '';?>">
    <div class="col-xs-12" style="text-align: center;margin-top:20px;font-weight:bold;">
        <?=get_phrase('monthly_balance_period','Monthly Balances for the Period Ending')?> <span id = "period"><?=$month;?></span>
    </div>
</div>

<div id = "table_holder" class="row <?=$this->session->system_admin ? 'hidden_elem' : '';?>">
    <div class="col-xs-12">
       
        <table class = 'table' id = "datatable" style = "white-space: nowrap;">
            <thead>   
                <tr>
                    <th><?=get_phrase('office_code','Office Code')?></th>
                    <th class = "no-sort"><?=get_phrase('totals');?></th>
                </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
    </div>
</div>


<script>

    const system_admin = '<?=$this->session->system_admin;?>';

    let datatable = $("#datatable").DataTable()
   
   $("#datatable_filter").html(search_box());

    function search_box(){
    return '<?=get_phrase('search');?>: <input type="form-control" onchange="search(this)" id="search_box" aria-controls="datatable" />';
    }

    function search(el){
    datatable.search($(el).val()).draw();
    }

    $("#date_range").datepicker({
        format: 'yyyy-mm-dd',
        minViewMode: 1,
        autoclose: true
    })

    function hideZeroAmountColumns(){
        if(datatable.data().any()){ // Check whether datatable has any data
           // Hide columns with '0.00' values
            let columnsCount = datatable.columns().header().length;
            for (var colIndex = 0; colIndex < columnsCount; colIndex++) {
                if (isColumnAllZeros(colIndex)) {
                    datatable.column(colIndex).visible(false);
                }else{
                    datatable.column(colIndex).visible(true);
                }
            }  
        }
    }
    
     // Function to check if all cells in a column have value '0.00'
     function isColumnAllZeros(columnIndex) {
        let allZeros = true;
        
        datatable.column(columnIndex).nodes().each(function (cell) {
        
            if ($(cell).text() !== '0.00') {
                allZeros = false;
                return false; // Exit the loop early if any cell is non-zero
            }
        });
        return allZeros;
    }
    
    function populate_account_select(columns){
        let options = '<option value = "">Select a filter account</option>';

        $.each(columns, function (index, column){
            if(column.hasOwnProperty('id')){
                options += "<option value = '" + column.id + "'>" + column.title + "</option>"
            }
        })

        $("#accounts").html(options)
        $('#accounts option:first').prop('selected', true).trigger('change.select2');
    }
        
    $('#report_category, #date_range, #accounts, #account_system_id').on('change', function () {

        if($(this).attr('id') == 'account_system_id' && $(this).val() == ""){
            return false;
        }

        if($(this).attr('id') == 'date_range'){
            if ($('#report_category').val() == '') {
                $('#report_category').val('fund_balance');
            }
            $('#report_category').trigger('change')
        }else{
            triggerInit($(this))
        }

    })

    function triggerInit(elem){
        let columns_url = "<?=base_url();?><?=$this->controller;?>/fund_columns";
        let data_url = "<?=base_url();?><?=$this->controller;?>/fund_show_list";
        let elem_id = $(elem).attr('id')
        const data = {
            accounts: elem_id != 'accounts' ? '' : $("#accounts").val()
        }
        let report_category = $('#report_category').val()

        $('#period').html($("#date_range").val())

        if(report_category == 'project_balance'){
            columns_url = "<?=base_url();?><?=$this->controller;?>/civ_columns";
            data_url = "<?=base_url();?><?=$this->controller;?>/civ_show_list";
        }

        $.post(columns_url, data, function ( response ) {

            const columns =JSON.parse(response)

            if(datatable){
                datatable.destroy();
                $('#datatable').empty(); 
            }

            if(elem_id != 'accounts'){
                populate_account_select(columns)
            }
            
            datatable = datatableInitilization(data_url, columns)
        });

        return datatable
    }

    function datatableInitilization(data_url, columns){
            return $('#datatable').DataTable({
                    dom: 'lBfrtip',
                    buttons: [
                        {
                            extend: 'excel',
                            exportOptions: {
                                columns: ':visible' // Only export visible columns
                            }
                        }
                    ],
                    pagingType: "full_numbers",
                    // stateSave:true,
                    pageLength:10,
                    order:[],
                    serverSide:true,
                    processing:true,
                    language:{processing:'Loading ...'},
                    ordering: true,
                    "scrollY": "500px", // Adjust the height according to your needs
                    "scrollX": true,
                    "scrollCollapse": true,
                    "lengthMenu": [ [10, 25, 50, 100, 500, 1000], [10, 25, 50, 100, 500, 1000] ],
                    columnDefs: [{
                        orderable: false,
                        targets: "no-sort"
                    }],
                    ajax:{
                        url:data_url,
                        type:"POST",
                        data: function(d) {
                            // Pass the selected date to the server

                            // if(refresh_account_selector){
                            //     $('#accounts option:first').prop('selected', true).trigger('change.select2');
                            // }

                            d.date_range = $("#date_range").val()
                            d.accounts = $("#accounts").val()
                            
                            if(system_admin){
                                d.account_system_id = $("#account_system_id").val();
                                // alert(d.account_system_id)
                            }
                            
                        },
                        "beforeSend": function() {
                            // Show the loading indicator when the request is sent
                            $('#overlay').css('display', 'block');
                        },
                        "complete": function() {
                            // Hide the loading indicator when the request is complete
                            $('#overlay').css('display', 'none');
                        }
                    },
                    serverSide: true,
                    "columns": columns,
                    drawCallback: function (settings){

                        const response = settings.json;
                        const account_system_selected = response.account_system_selected

                        if(account_system_selected && system_admin){
                            $('#warning_holder').addClass('hidden_elem')
                            $('#month_label_holder, #table_holder').removeClass('hidden_elem')
                        }
                        
                        // Hide columns with zero values
                        hideZeroAmountColumns()

                        // Header Update date label
                        $('#period').html($("#date_range").val())
                    }
                });
    }

    $('#account_system_id').on('change', function () {
        // alert('Hello')
        const account_system_id = $('#account_system_id').val()

        if(account_system_id > 0){
            $('#table_holder, #month_label_holder').removeClass('hidden_elem')
            $('#warning_holder').addClass('hidden_elem')
        }else{
            $('#table_holder, #month_label_holder').addClass('hidden_elem')
            $('#warning_holder').removeClass('hidden_elem')
        }
       
    })

    $(document).on('click','#datatable tbody tr', function() {
        datatable.$('tr.selected').removeClass('selected');
        $('td.highlight').removeClass('highlight');

        // Highlight the clicked row
        $(this).addClass('selected');

        // Get the index of the clicked row
        var rowIndex = datatable.row(this).index();

        // Highlight the corresponding column cells
        // datatable.column(rowIndex).nodes().to$().addClass('highlight');
    });
</script>