<?php

// echo $config_file;

?>

<div class="row" style="margin-top:45px;">
    <div class="col-xs-4">
        <div class="pull-right"><?=get_phrase('search_config_phrase');?></div>
    </div>
    <div class="col-xs-4">
        <input type="text" id="search" class="form-control" />
    </div>
</div>

<div class="row" style="margin-top:45px;">
    <div class="col-xs-12">
        <?php

        
        ob_start();
        include APPPATH.$config_name.DIRECTORY_SEPARATOR.$config_file.".php";
        $arr = ob_get_contents();
        ob_end_clean();

        if($config_array_name == 'lang'){
            $chunks = array_chunk($lang,2,true);
        }else{
            $chunks = array_chunk($config,2,true);
        }
        
        //print_r($columns);
      ?>
        <form id="form">
            <table id="phrase_table" class="table">
                <thead>
                    <tr>
                        <th colspan="2" style="font-weight:bolder;text-align:center;"><?=get_phrase($config_name.'_configurations');?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                            //$max_rows = 0;
                            foreach($chunks as $row){
                                //if($max_rows == 3) break;
                        ?>
                    <tr>
                        <?php        
                                foreach($row as $config_key => $phrase){
                        ?>
                        
                        <td>                                
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <span><?=ucfirst(str_replace("_"," ",$config_key));?></span>
                                    </span>	
                                    <input type="config" class="form-control config" value="<?=$phrase;?>" name="<?=$config_key;?>" id="<?=$config_key;?>" aria-label="Config Phrase" placeholder="<?=get_phrase('configuration_phrase');?>">
                                </div>
                        </td>
                        <?php 
                                }
                        ?>
                    </tr>
                    <?php
                             //$max_rows++;        
                            }
                        ?>
                </tbody>
            </table>

        </form>
    </div>
</div>



<script>
$("#search").keyup(function() {
    var value = this.value.toLowerCase().trim();
    
    $("table tr").each(function(index) {
        if (!index) return;
        $(this).find("td").each(function() {
            var id = $(this).find('input').val().toLowerCase().trim();

            if (id.indexOf(value) != -1) {
                $(this).find('input').css('border', '3px solid orange');
            } else {
                $(this).find('input').removeAttr('style');
            }

            var not_found = (id.indexOf(value) == -1);
            $(this).closest('tr').toggle(!not_found);
            return not_found;
        });
    });
});

$(".config").change(function() {
    var url = "<?=base_url().$this->controller;?>/update_config";
    //var data = $("#form").serializeArray();
    var data = {
        'key': $(this).attr('name'),
        'phrase': $(this).val(),
        config_name: "<?=$config_name;?>",
        config_array_name: "<?=$config_array_name;?>",
        config_file: "<?=$config_file;?>",
    };

    $.ajax({
        url: url,
        type: "POST",
        data: data,
        beforeSend: function() {

        },
        success: function(resp) {
            //alert(resp);
        },
        error: function() {

        }
    });
});
</script>