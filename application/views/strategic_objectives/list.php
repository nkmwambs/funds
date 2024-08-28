<style>
    .fa-plus{
        cursor: pointer;
    }
</style>

<div class="row" style="margin-bottom:25px;">
  <div class="col-xs-12" style="text-align:center;">
        <?php 
            extract($result);
            if($show_add_button && $this->user_model->check_role_has_permissions(ucfirst($this->controller),'create')){
                echo add_record_button($this->controller, $has_details_table,null,$has_details_listing, $is_multi_row);
              }
        ?>
    </div>
</div>


<div class="row">
    <div class="col-xs-12">
        <table class="table table-striped" id="datatable">
            <thead>
                <tr>
                    
                    <?php 
                        foreach($columns as $column){
                            if(is_array($column)){
                                $column = $column['list_columns'];
                            }
                    ?>
                            <th><?=get_phrase($column);?></th>
                    <?php
                        }
                    ?>
                    <th><?=get_phrase('show_interventions');?></th>
                </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
    </div>
</div>

<script>
    //$(document).ready(function(){
        var url = "<?=base_url();?><?=$this->controller;?>/show_list";
        const datatable = $("#datatable").DataTable({
            dom: 'lBfrtip',
            buttons: [
                'copyHtml5',
                'excelHtml5',
                'csvHtml5',
                'pdfHtml5',
            ],
            pagingType: "full_numbers",
            // stateSave:true,
            pageLength:10,
            order:[],
            serverSide:true,
            processing:true,
            language:{processing:'Loading ...'},
            ajax:{
                url:url,
                type:"POST",
            }
        });
   // });

   $("#datatable_filter").html(search_box());

    //});

    function search_box(){
    return '<?=get_phrase('search');?>: <input type="form-control" onchange="search(this)" id="search_box" aria-controls="datatable" />';
    }

    function search(el){
    datatable.search($(el).val()).draw();
    }

// Add event listener for opening and closing details
datatable.on('click', '.fa-plus', function (e) {
    let tr = e.target.closest('tr');
    let row = datatable.row(tr);
   
    if (row.child.isShown()) {
        // This row is already open - close it
        row.child.hide();
    }
    else {
        const url = '<?=base_url();?>strategic_objectives/get_objectives_interventions/'
        const objective_id = $(this).attr('id')
        const data = {
            objective_id
        }
        // Open this row
        $.post(url, data, function (interventions) {
            const interventions_obj = JSON.parse(interventions)
            row.child(format_intervention_list(interventions_obj)).show();
        })
    }
});

function format_intervention_list(interventions){
    let list = '<p><b><?=get_phrase('interventions_list','List of Interventions');?></b></p><ul>'

    $.each(interventions, function (i,elem) {
        list += '<li>' + elem + '</li>'
    })

    list += '</ul>'

    return list
}

</script>