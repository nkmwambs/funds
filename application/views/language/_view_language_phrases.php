<?php 
    // extract($result);

    $count_columns = 3;

    $chunked_phrases = array_chunk($phrases,$count_columns,true);

?>

<style>
    .phrase {
        width: 650px;
        height: 50px;
        padding: 10px;
    }

    #phrase_header {
        font-weight:bold;
        text-align: center;
        padding-bottom: 30px;
    }

    #phrases_table {
        width: 100%;
    }

    .save {
        cursor: pointer;
    }

</style>

<table id = 'phrases_table'>
    <thead>
        <tr>
            <th id = "phrase_header" colspan = "<?=$count_columns;?>"><?=get_phrase('available_phrase', 'Available phrase for translation');?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($chunked_phrases as $row){?>
            <tr>
                <?php foreach($row as $handle => $phrase){?>
                    <td><div id = "<?=$handle;?>" class = "phrase form-group"><?=$phrase;?></div></td>
                <?php }?>
            </tr>
        <?php }?>
    <t/tbody>
</table>

<script>
    // $(".phrase").on('dblclick', function () {
    //     const old_phrase = $(this).html();
    //     const id = $(this).attr('id');

    //     // alert(id);

    //     let input = "";
    //     input += "<div class = 'col-sm-11'><input type = 'text' class = 'form-control input_phrase' id = '" + id + "'  value = '" + old_phrase + "' /></div>";
    //     input += "<div class = 'col-sm-1'><i class = 'fa fa-save save'></i></div>";

    //     $(this).html(input);
    // });

    // $(document).on('click','.save', function () {

    //     const handle = $(this).closest('.form-group').find('.input_phrase').attr('id');
    //     const phrase = $(this).closest('.form-group').find('.input_phrase').val();
    //     const url = "<?=base_url();?>language/translate_phrase";
    //     const data = {
    //         handle: handle,
    //         phrase: phrase
    //     };
        
    //     $.post(url, data, function (response) {
    //         alert(response);
    //     });
    // })
</script>