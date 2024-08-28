<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
//echo hash_id($this->id,'decode');

extract($result);
?>

<div class="row">
  <div class="col-xs-12">
      <?=Widget_base::load('position','position_1');?>
  </div>
</div>

<div class="row">
	<div class="col-md-12">
		<div class="panel panel-default" data-collapsed="0">
        	<div class="panel-heading">
            	<div class="panel-title" >
            		<i class="entypo-plus-circled"></i>
					<?php echo get_phrase('edit_'.$this->controller);?>
            	</div>
            </div>
			
            <div class="panel-body">

              <?php echo form_open(base_url().$this->controller.'/edit/'.$this->uri->segment(3,0) , array('class' => 'form-horizontal form-groups-bordered', 'enctype' => 'multipart/form-data'));

                  //foreach ($keys as $column => $value) {
                  foreach ($fields as $column => $field) {

                    if( 
                        // strpos($column,'_id') == true ||
                        // strpos($column,'_track_number') == true ||
                        // strpos($column,'_created_date') == true ||
                        // strpos($column,'_last_modified_date') == true ||
                        // strpos($column,'_created_by') == true ||
                        // strpos($column,'_last_modified_by') == true
                        preg_match("/_id$/", $column) ||
                        preg_match("/_track_number$/", $column) ||
                        preg_match("/_created_date$/", $column) ||
                        preg_match("/_last_modified_date$/", $column) ||
                        preg_match("/_created_by$/", $column) ||
                        preg_match("/_last_modified_by$/", $column)

                    ){
                      continue;
                    }
                ?>
                  <div class="form-group">
                    <label for="" class="control-label col-xs-3"><?=ucwords(str_replace("_"," ",$column));?></label>
                    <div class="col-xs-9">
                      <?php
                        //echo $this->grants->header_row_field($column, $value);
                        //echo $value;
                        echo $field;
                      ?>
                    </div>
                  </div>
                <?php
                  }
                 ?>


                 <div class="form-group">
                   <div class="col-xs-12" style="text-align:center;">
                     <button class="btn btn-default edit back"><?=get_phrase('save');?></button>
                     <button class="btn btn-default edit_continue"><?=get_phrase('save_and_continue');?></button>
                   </div>
                 </div>
               </form>  
          </div>
      </div>
    </div>
</div>

<script>

var code_field_or_element='';

$('.form-control').keydown(function(){
  $(this).removeAttr('style');

  //Code added by Onduso
  var code_elem=$(this).attr('id');

//check if we have code
let code_str_exist=code_elem.split('_');

if(code_str_exist.includes('code')){
  code_field_or_element=code_elem;
}

//End of addition by Onduso
});

$(".edit, .edit_continue").on('click',function(ev){
  
  var elem = $(this);


   //Check if all required fields are filled
   var empty_fields_count = 0;
  $('.form-control').each(function(i,el){
    if($(el).hasClass('select2')){
      //$(el).find(':selected');
    }else{
      if($(el).val().trim() == '' && $(el).attr('required')){
        $(el).css('border','1px solid red');
        empty_fields_count++;
      }
    }
  });

  if(empty_fields_count>0){
    alert('1 or more required fields are empty');
  }else{

  pre_record_post();

  var url = "<?=base_url().$this->capped_controller;?>/<?=$this->action;?>/<?=$this->uri->segment(3,0);?>";

  var data = $(this).closest('form').serializeArray();

  $.ajax({
    url:url,
    data:data,
    type:"POST",
    beforeSend:function(){

    },
    success:function(response){
      //alert(response);
      
      //Check if the dulicate columns for some functions exists [Added or modified By Onduso 28/7/2022]
      var convert_response_json = JSON.parse(response);

      if (convert_response_json['flag'] == false) {

        $('#'+code_field_or_element).css('border', '1px solid red');
        
        alert(convert_response_json['message']);
      
        return false;

      }else{
        on_record_post();

        // alert(response)
        alert(convert_response_json['message']);

        //If Edit , use the browser history and go back
        if(elem.hasClass('back')){
          location.href = document.referrer      
        } 

      }

      
    

    },
    error:function(){

    }
  });

  post_record_post();
}  
  ev.preventDefault();
});

// $('.datepicker').datepicker(
//   {
//     format:'yyyy-mm-dd',
//   }
// );

</script>