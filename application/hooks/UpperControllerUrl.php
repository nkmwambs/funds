<?php 

class UpperControllerUrl{
    public function run(){
        if($_SERVER['HTTP_HOST'] !== 'localhost'){
            $_SERVER['REQUEST_URI'] = $this->cap_url_controller($_SERVER['REQUEST_URI']);
        }
    }

    function cap_url_controller($url){
		$url_segments = parse_url($url);

		$path_array = explode("/",ltrim($url_segments['path'],'/'));

		array_shift($path_array);

		$arguments_array = array_map(function($segment,$index){

		if($index == 0){
			return ucfirst($segment);
		}else{
			return $segment;
		}

		},$path_array,array_keys($path_array));

		echo implode("/",$arguments_array);
    }
}