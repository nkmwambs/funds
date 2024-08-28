<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Livingstone Onduso
 *	@date		: 24th March, 2022
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *  londuso@ke.ci.org
 */
use League\Csv\Reader;

class CSVReader_library {

    var $fields;            /** columns names retrieved after parsing */
    var $separator  =   ';';    /** separator used to explode each line */
    var $enclosure  =   '"';    /** enclosure used to decorate each field */

    var $max_row_size   =   0;    /** maximum row size to be used for decoding */

    function read_CSV_file($filepath) 

    {
       
        $file           =   fopen($filepath, 'r');
     
        $this->fields   =   fgetcsv($file, $this->max_row_size, $this->separator, $this->enclosure);
        $keys_values        =   explode(',',$this->fields[0]);

        $content            =   array();

        //Remove from the column  UTF-8 BOM  from headings
        $column_heading = preg_replace('/[\x00-\x1F\x80-\xFF]/', '',$this->sanitise_string($keys_values));

        $row_count  =   1;
        while (($row = fgetcsv($file, $this->max_row_size, $this->separator, $this->enclosure)) != false)
        {
            if( $row != null ) { // skip empty lines
                $values         =   explode(',',$row[0]);
                if(count($column_heading) == count($values)){
                    $column=   array();
                    $new_values =   array();
                    $new_values =   $this->sanitise_string($values);

                    for($column_count=0;$column_count<count($column_heading);$column_count++){
                        if($column_heading[$column_count]    !=  ""){
                            $column[$column_heading[$column_count]] =   $new_values[$column_count];
                        }
                    }
                    $content[$row_count]    =   $column;
                    $row_count++;
                }
            }
        }
        fclose($file);
        return $content;
    }

    function sanitise_string($data)
    {
        $result =   array();
        foreach($data as $row){
            $result[]   =   str_replace('"', '',$row);
        }
        return $result;
    }  
    
    //Test

    public function test($target_file){
        // Parse the CSV file
        $csv = Reader::createFromPath($target_file);
        $csv->setDelimiter(',');
        $csv->setHeaderOffset(0);
        $headers = $csv->getHeader(); // Get the header row
    }
}
?>