<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
/*
 *  @author     : Livingtone Onduso
 *  @date       : 27th September, 2021
 *  Finance management system for NGOs
 *  https://techsysnow.com
 *  londuso@ke.ci.org
 */

?>
<style>
    td {
        white-space: nowrap
    }
</style>
<div class="row" style="margin-bottom:25px;">
    <div class="col-xs-12" style="text-align:center;">
        <?php
        extract($result);

        if ($show_add_button && $this->user_model->check_role_has_permissions(ucfirst($this->controller), 'create')) {
            echo add_record_button($this->controller, $has_details_table, null, $has_details_listing, $is_multi_row);
        }
        ?>
    </div>
</div>

<div class="row">
    <div class="col-xs-12">
        <table class="table table-striped" id="datatable" width="100%">
            <thead>
                <tr>
                    <th nowrap><?= get_phrase('action'); ?></th>
                    <?php
                    foreach ($columns as $column) {
                    ?>
                        <th><?= get_phrase($column); ?></th>
                    <?php
                    }
                    ?>
                </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
    </div>
</div>

<script>
  
$('.btn-danger').on('click',function(){

// var $td = $(this).closest('tr').children('td');

// var wd = $td.eq(3).text();

// console.log(wd);

alert('Yes');
});
   
    var url = "<?= base_url(); ?><?= $this->controller; ?>/show_list";
    const datatable = $("#datatable").DataTable({
        dom: 'lBfrtip',
        buttons: [
            'copyHtml5',
            'excelHtml5',
            'csvHtml5',
            'pdfHtml5',
        ],
        pagingType: "full_numbers",
        stateSave:true,
        pageLength: 10,
        order: [],
        serverSide: true,
        processing: true,
        language: {
            processing: 'Loading ...'
        },
        ajax: {
            url: url,
            type: "POST",
        }
    });


    // $("#datatable_filter").html(search_box());

    //});

    // function search_box() {
    //     return '<?= get_phrase('search'); ?>: <input type="form-control" onchange="search(this)" id="search_box" aria-controls="datatable" />';
    // }

    // function search(el) {
    //     datatable.search($(el).val()).draw();
    // }
</script>