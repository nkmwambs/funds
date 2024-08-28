<?php 

if ( ! function_exists('funder_projects_select')){
    function funder_projects_select($funder_projects){
            $select = "<select id='funder_projects_select' class='form-control'>
                    <option>Select Funder Projects ...</option>";
                    foreach($funder_projects as $funder_project){
                        $select .="<optgroup label='".$funder_project['funder']['funder_name']."'>";
                            foreach($funder_project['projects'] as $project){
                                $select .= "<option value='".$project['project_allocation_id']."'>";
                                    $select.= $project['project_allocation_name'];
                                $select .= "</option>";
                        }
                    $select .= "</optgroup>";
                 }

            $select .= "</select>";

        return $select;            
    }
}

if ( ! function_exists('hierarchy_office_select')){
    function hierarchy_office_select(){
        $CI =& get_instance();
        $items = $CI->session->hierarchy_offices;

        $select ="<select class='form-control' id='office' name='fk_office_id'>";
        $select .="<option>".get_phrase('select_office')."</option>";
            foreach($items as $office){
                $select .= "<option value='".$office['office_id']."'>".$office['office_name']."</option>";
            }
        $select .= "</select>";

        return $select;
    }
}      