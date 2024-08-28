<style>
    .btn_action{
        cursor:pointer;
    }
</style>

<?php
//print_r($result);
extract($result);
?>

<div class='row'>
    <div class='col-xs-12'>
        <table class='table table-striped'>
            <thead>
                <tr>
                    <th><?=get_phrase('action');?></th>
                    <?php foreach($fields as $field_name => $field_element){?>
                        <th><?=get_phrase($field_name);?></th>
                    <?php }?>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div class='btn btn-success btn-icon add_row'><i class='fa fa-plus-circle'></i><?=get_phrase('add');?></div>
                        <div class='btn btn-danger btn-icon delete_row'><i class='fa fa-minus-circle'></i><?=get_phrase('delete');?></div>
                    </td>
                    <?php foreach($fields as $field_name => $field_element){?>
                        <td><?=$field_element;?></td>
                    <?php }?>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
    $(document).ready(function(){
        $("span.input-group-addon").remove();
        $('div.input-group').removeClass('input-group');
        $('select').removeAttr('multiple');
    });

    $(".add_row").on('click',function(){
        alert('Add row');
    })

    $(".delete_row").on('click',function(){
        alert('Delete row');
    })
</script>
