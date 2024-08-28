<form id="my_dropzone"  class="dropzone"></form>     

<script>

$(document).ready(function(){

    var myDropzone = new Dropzone("#my_dropzone", { 
        url: "<?=base_url()?>Request/create_uploads_temp",
        paramName: "file", // The name that will be used to transfer the file
        maxFilesize: 5, // MB
        uploadMultiple:true,
        acceptedFiles:'image/*,application/pdf',    
    });

    myDropzone.on("complete", function(file) {
        //myDropzone.removeFile(file);
        //myDropzone.removeAllFiles();
        //alert(myDropzone.getAcceptedFiles());
    }); 

    myDropzone.on("success", function(file,response) {
        if(response == 0){
            alert('Error in uploading files');
            return false;
        }

    });  





});

</script>