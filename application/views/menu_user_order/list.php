<link href = "https://code.jquery.com/ui/1.10.4/themes/ui-lightness/jquery-ui.css"
         rel = "stylesheet">
<script src = "https://code.jquery.com/jquery-1.10.2.js"></script>
<script src = "https://code.jquery.com/ui/1.10.4/jquery-ui.js"></script>
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">

<?php 
//print_r($result);

extract($result);

?>

<style>
  .default {
    background: #cedc98;
    border: 1px solid #DDDDDD;
    color: #333333;
  }

  #sortable-main-menu, #sortable-sub-menu { list-style-type: none; margin: 0; padding: 0; width: 80%; }
  #sortable-main-menu li span,#sortable-sub-menu li span { position: absolute; margin-left: -1.3em; }
  #sortable-main-menu li, #sortable-sub-menu li{
    margin: 0 3px 3px 3px;
    padding-top: 0.4em;
    padding-left: 1.5em;
    padding-bottom: 1.4em;
    font-size: 1.4em;
    height: 18px;
}

.ui-icon {
  /* position:relative;
  left:-20px;
  font-size:25px; */
}

.highlight {
  border: 1px solid red;
  font-weight: bold;
  font-size: 45px;
  background-color: #333333;
}

.default {
  background: #cedc98;
  border: 1px solid #DDDDDD;
  color: #333333;
}

</style>

<div class='row'>
  <div class='col-xs-12' style='text-align:center;'>
    <a href='<?=base_url();?>menu_user_order/list' class='btn btn-default'><i class='fa fa-refresh'></i> <?=get_phrase('reload');?></a>
  </div>
</div>
 

<div class='row'>
  <div class='col-xs-6'>
  <h3><span id = "sortable-9"></span></h3>
      <ul id = "sortable-main-menu"><h4 style='text-align:center;'>Top Menu</h4>
        <?php foreach($main_menu as $menu_item){?>
          <li id='<?=$menu_item['menu_id'];?>' class="ui-state-default <?=strtolower($menu_item['menu_name']);?>"><span class="ui-icon ui-icon-arrowthick-2-n-s"></span><?=str_replace('_',' ',$menu_item['menu_name']);?></li>
        <?php }?>
        
      </ul>
  </div>

  <div class='col-xs-6'>
      <ul id = "sortable-sub-menu"><h4 style='text-align:center;'>Sub Menu</h4>
        <?php foreach($sub_menu as $menu_item){?>
          <li id='<?=$menu_item['menu_id'];?>' class="ui-state-default <?=strtolower($menu_item['menu_name']);?>"><span class="ui-icon ui-icon-arrowthick-2-n-s"></span><?=str_replace('_',' ',$menu_item['menu_name']);?></li>
        <?php }?>
      </ul>
  </div>
</div>


<script>
  $(function() {
    $("#sortable-main-menu, #sortable-sub-menu" ).sortable({
        connectWith: "#sortable-main-menu, #sortable-sub-menu",
        placeholder: "highlight",
        dropOnEmpty: false,
        //cancel: ".<?=strtolower($this->config->item('default_launch_page'));?>",
        //items: "li:not(.dashboard)",
        update: function(event, ui) {
                  var menu_name = $(this).sortable('widget').attr('id');
                  var menuorder = $(this).sortable('toArray').toString();
                  // var ajax_ids = $(this).sortable( "serialize", { key: "sort" } );
                  // $("#sortable-9").text (menuorder);

                  var url = "<?=base_url();?>menu_user_order/update_menu_order";

                  $.ajax({
                    url:url,
                    data:{'menuorder':menuorder,'menu_name':menu_name},
                    type:"POST",
                    global: false,
                    success:function(response){
                      //alert(response);
                    },
                    error:function(error){
                      alert('Error occurred!');
                    }
                  })
                  
               }
      });
      
  });
  
</script>