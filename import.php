<?php 
set_time_limit(10000);
include('configs.php');
require_once('utils.php');
$Utils = new Utils(Array(
	'db_host' => DB_HOST,
	'db_user' => DB_USER,
	'db_pass' => DB_PASS,
	'db_name' => DB_NAME,
	'db_table'=> DB_TABLE_IMPORT
), 'mysqli', EXPLODE_HEADER, EXPLODE_DATA, FIELD_END_OF_HEADERLINE, CSV_FILENAME);

$Utils->db_connect();
$csvfile = fopen(CSV_FILENAME, 'r');
$header = fgets($csvfile);
$tablefields = $Utils->syunc_fields($header);
$data_values = $Utils->create_default_values_fields($tablefields);
$sort_array = Array();
$sort_position = 1;
while (!feof($csvfile))
{
	$csv_data = fgets($csvfile, 1024);
	if ($csv_data != ''){
		$csv_data = $Utils->get_array_fields_csvdata($csv_data, 'data');
		$values = $data_values['default_format_values'];
		$image_position = 1;
		
		foreach ($data_values['default_format_values'] as $key => $val){
			$index = $Utils->find_index($tablefields, $key);
			if ($index != -1){			
				if(strpos($key, 'product_url') !== false){
					$product_code = explode('-', $csv_data[0]);
					$image_name = Array();
					if (count($product_code) > 0){
						$image_name[] = $product_code[0];
					}
					if (count($product_code) > 1){
						$image_name[] = $product_code[1];
					}
					$name = implode('-', $image_name).'_'.$image_position;
					$image_url = PRODUCT_BASEIMAGE_URL_1000PX.$name.".jpg";
					if (CHECK_PRODUCT_IMAGE_EXISTS){
						if ($Utils->is_image($image_url)){
							$values[$key] = $Utils->escape_string($image_url);
						}else{
							$values[$key] = "''";
						}
					}else{
						$values[$key] = $Utils->escape_string($image_url);
					}
					$image_position++;
				}else if($key == '`orderable`'){
					$values[$key] = $sort_position;
				}else{
					$values[$key] = $Utils->escape_string($csv_data[$index]);
				}
				if (empty($values[$key])){
					$values[$key] = "''";
				}
				if ($key == '`talla`'){
					$price = str_replace("'", '', $values[$key]);
				}
			}
		}
		if (!$Utils->check_value($csv_data[0])){
			$query  = "INSERT INTO ".DB_TABLE_IMPORT."(".implode(",", $data_values['fields']).")";
			$query .= "VALUES(".implode(",", $values).")";
			$Utils->executeSQL($query);
			$insert_id = $Utils->getRows("SELECT max(id) AS sort FROM ".DB_TABLE_IMPORT." ORDER BY id DESC LIMIT 1");
			if (isset($insert_id[0]['sort'])){
				$sort_array[] = Array('id'=> $insert_id[0]['sort'], 'position'=> $sort_position, 'price' => (float) $price);
			}
			$sort_position++;
		}
	}
}
$sort_array = $Utils->array_sort($sort_array, 'price', SORT_ASC);
foreach ($sort_array as $position => $item){
	$Utils->executeSQL("UPDATE ".DB_TABLE_IMPORT." SET orderable = ".++$position." WHERE id = ".$item['id']);
}
fclose($csvfile);
$Utils->db_close();
?>