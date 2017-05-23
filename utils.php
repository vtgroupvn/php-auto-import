<?php
/**
 * 
 * Utils class with more functions to help(Check|ExecuteSQL|Connect|Close|Sort|etc)
 * 
 * @author cuong
 *
 */
class Utils
{
	private $_configs = Array();
	private $_mysql = null;
	private $_type = 'mysqli';
	private $_explode_header = ',';
	private $_explode_data   = ';';
	private $_end_field_csv_header = '';
	private $_csv_filename = '';
	function __construct($configs = Array(), $type = 'mysqli', $explode_header = ',', $explode_data = ';', $end_field_csv_header = '', $csv_filename = '')
	{
		$this->_configs = $configs;
		$this->_configs['db_host']   = (!isset($this->_configs['db_host']) ? 'localhost' : $this->_configs['db_host']); 
		$this->_configs['db_user']   = (!isset($this->_configs['db_user']) ? 'root' : $this->_configs['db_user']);
		$this->_configs['db_pass']   = (!isset($this->_configs['db_pass']) ? '' : $this->_configs['db_pass']);
		$this->_configs['db_name']   = (!isset($this->_configs['db_name']) ? '' : $this->_configs['db_name']);
		$this->_configs['db_table']  = (!isset($this->_configs['db_table'])? '' : $this->_configs['db_table']);
		$this->_type                 = $type;
		$this->_explode_header       = $explode_header;
		$this->_explode_data         = $explode_data;
		$this->_csv_filename         = $csv_filename;
		$this->_end_field_csv_header = $this->field_replace($end_field_csv_header);
		if (function_exists('mysql_connect') && $type == 'mysql'){
			$this->_type = 'mysql';
		}elseif(!function_exists('mysql_connect') && $type == 'mysql'){
			$this->_type = 'mysqli';
		}
		if (class_exists('mysqli') && $type == 'mysqli'){
			$this->_type = 'mysqli';
		}elseif(!class_exists('mysqli') && $type == 'mysqli'){
			$this->_type = 'mysql';
		}
	}
	public function db_connect()
	{
		if ($this->_type == 'mysqli'){
			$this->_mysql = new mysqli(
				$this->_configs['db_host'], 
				$this->_configs['db_user'], 
				$this->_configs['db_pass'], 
				$this->_configs['db_name']
			);
			if ($this->_mysql->connect_errno) {
			    printf("Connect failed: %s\n", $this->_mysql->connect_errno);
			    exit();
			}
		}else{
			$this->_mysql = mysql_connect(
				$this->_configs['db_host'], 
				$this->_configs['db_user'], 
				$this->_configs['db_pass']
			);
			if (!$this->_mysql) {
			    die("Connect failed: ". mysql_error());
			}
			mysql_select_db($this->_configs['db_name'], $this->_mysql) or die('Could not select database '.mysql_error());
		}
	}
	public function db_close()
	{
		if (is_resource($this->_mysql)){
			if ($this->_type == 'mysqli'){
				$this->_mysql->close();
			}else{
				mysql_close($this->_mysql);
			}
			$this->_mysql = null;
		}
	}
	public function getRows($query)
	{
		$rows = array();
		$result = $this->executeSQL($query);
		if ($this->_type == 'mysqli'){
			while ($row = @$result->fetch_assoc()) {
				$rows[] = $row;
			}
			if (method_exists($result, 'free_result')){
				$result->free_result();
			}elseif (function_exists('mysqli_free_result')){
				@mysqli_free_result($result);
			}
		}else{
			while ($row = mysql_fetch_assoc($result)){
		    	$rows[] = $row;
		  	}
			if (function_exists('msql_free_result ')){
				@msql_free_result($result);
			}
		}
		return $rows;
	}
	public function executeSQL($query)
	{
		if ($this->_type == 'mysqli'){
			if ($result = $this->_mysql->query($query)){
				printf("Select success returned %d rows.\n", @$result->num_rows);
			}else{
				printf("Error invalid query: %s\nWhole query: %s\n", @$this->_mysql->error, $query);
				die();
			}
		}else{
			if ($result = mysql_query($query)){
				printf("Select success returned %d rows.\n", @mysql_num_rows($result));
			}else{
				printf("Error invalid query: %s\nWhole query: %s\n", mysql_error(), $query);
				die();
			}
		}
		return $result;
	}
	public function default_field($field)
	{
		return in_array($field, Array('`id`'));
	}
	public function check_field($field)
	{
		$check = $this->getRows("SHOW COLUMNS FROM `".$this->_configs['db_table']."` LIKE '".$field."'");
		return (count($check) > 0 ? true:false);	
	}
	public function get_array_fields_csvdata($str_data, $type = 'header')
	{
		if ($type == 'header'){
			switch ($this->_explode_header){
				case ',':
				case ';':
					$data = explode($this->_explode_header, $str_data);
					break;
				case '","':
				case '";"':
					$str_data = substr($str_data, 1);
					$data = explode($this->_explode_header, $str_data);
					if ($data[count($data)-1][strlen($data[count($data)-1])-2] == '"'){
						$data[count($data)-1][strlen($data[count($data)-1])-2] = '';
					}
					break;
			}		
		}else{
			switch ($this->_explode_data){
				case ',':
				case ';':
					$data = explode($this->_explode_data, $str_data);
					break;
				case '","':
				case '";"':
					$str_data = substr($str_data, 1);
					$data = explode($this->_explode_data, $str_data);
					if ($data[count($data)-1][strlen($data[count($data)-1])-2] == '"'){
						$data[count($data)-1][strlen($data[count($data)-1])-2] = '';
					}
					break;
			}
		}		
		
		return $data;
	}
	public function field_replace($field)
	{
		/*
		 * Simple string replace. it will be fast replace
		 */
		$str = str_replace(
			Array(' ', '/','\\', '[', ']', '`', '(', ')', '"', ' ', '-', '&', ';', '$', '@', '#','%','^', '+', '{', '}', '*', '!', '=', '?', ',', '.'. '\r', '\n', '\n\r'), 
			Array('', '', '', '','', '', '', '', '_', '_', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''), 
			$field
		);
		$exists = (strpos($str, '__') !== false);
		while ($exists){
			$str = str_replace('__', '_', $str);
			$exists = (strpos($str, '__') !== false);
		}
		$exists = (strpos($str, ' ') !== false);
		while ($exists){
			$str = str_replace(' ', '', $str);
			$exists = (strpos($str, ' ') !== false);
		}
		return strtolower(trim($str));
	}
	public function find_index($data, $find)
	{
		$found = -1;
		foreach ($data as $n => $val){
			$val = $this->field_replace($val);
			if ('`'.$val.'`' == $find){
				$found = $n;
				break;
			}elseif($n + 1 == count($data) && strpos($val, str_replace('`', '', $find)) !== false){
				$found = $n;
				break;
			}
		}
		return $found;
	}
	public function isInt($key)
	{
		$csvfile = fopen($this->_csv_filename, 'r');
		$header = fgets($csvfile);
		$header = $this->get_array_fields_csvdata($header, 'header');
		$csv_data = fgets($csvfile, 1024);
		$csv_data = $this->get_array_fields_csvdata($csv_data, 'data');
		$value    = '';
		$index = $this->find_index($header, $key);
		if ($index != -1){
			$value = $csv_data[$index];
			return ((int) $value > 0 ? true : false);
		}
		return false;
	}
	public function escape_string($data)
	{
		if (method_exists($this->_mysql, 'real_escape_string') || function_exists('mysql_escape_string')){
			if ($this->_type == 'mysqli'){
				$data = $this->_mysql->real_escape_string($data);
			}else{
				$data = mysql_escape_string($data);
			}
		}else{
			$data = addslashes($data);
		}

		return "'".$data."'";		
	}
	public function syunc_fields($header = '')
	{
		$tablefields = $this->get_array_fields_csvdata($header, 'header');
		$tablefields[] = 'orderable';
		foreach($tablefields as $key => $field){
			$alter_field = $this->field_replace($field);
			$tablefields[$key] = $alter_field;
			if (!$this->isInt($field)){
				$field_type = 'VARCHAR(100) NOT NULL';
			}else{
				$field_type = 'DOUBLE(10,5) NOT NULL';
			}	
			if (strpos($alter_field, $this->_end_field_csv_header) !== false){
				$alter_field = $this->_end_field_csv_header;
			}
			if (!$this->check_field($alter_field)){
				if ($this->executeSQL("ALTER TABLE `".$this->_configs['db_table']."` ADD `".str_replace(' ', '',$alter_field)."` ".$field_type." comment \"".$field."\"")){
					sleep(5);
				}
			}
		}
		for($i = 1; $i <= TOTAL_IMAGES; $i++){
			if (!$this->check_field('product_url_'.$i)){
				if ($this->executeSQL("ALTER TABLE `".$this->_configs['db_table']."` ADD `product_url_".$i."` TEXT comment \"Product Image Url\"")){
					sleep(5);
				}
			}
			$tablefields[] = 'product_url_'.$i;
		}
		return $tablefields;
	}
	public function create_default_values_fields($tablefields = Array())
	{
		$fields = Array();
		$fields[] = '`id`';
		foreach ($tablefields as $key => $val){
			if (strpos($val, $this->_end_field_csv_header) !== false){
				$fields[] = '`'.$this->_end_field_csv_header.'`';
			}else{
				$fields[] = '`'.strtolower($val).'`';
			}
		}
		
		$default_format_values = array();
		$default_format_values['`id`'] = 0;
		foreach ($fields as $key => $val){
			if (!$this->default_field($val)){
				$default_format_values[$val] = "''";
			}
		}
		return array('fields'=>$fields, 'default_format_values'=>$default_format_values);
	}
	public function check_value($value)
	{
		$rows = $this->getRows("SELECT id FROM ".$this->_configs['db_table']." WHERE articulo = '".$value."'");
		return (count($rows) > 0?true:false);
	}
	public function is_image($external_link)
	{
		try {
		    $imageinfo = getimagesize($external_link);
		} catch (Exception $e) {
		    $imageinfo = false;
		}
		return $imageinfo;
	}
	public function array_sort($array, $on, $order = SORT_ASC)
	{
	    $new_array = array();
	    $sortable_array = array();
	    if (count($array) > 0) {
	        foreach ($array as $k => $v) {
	            if (is_array($v)) {
	                foreach ($v as $k2 => $v2) {
	                    if ($k2 == $on) {
	                        $sortable_array[$k] = $v2;
	                    }
	                }
	            } else {
	                $sortable_array[$k] = $v;
	            }
	        }
	        switch ($order) {
	            case SORT_ASC:
	                asort($sortable_array);
	            break;
	            case SORT_DESC:
	                arsort($sortable_array);
	            break;
	        }
	        foreach ($sortable_array as $k => $v) {
	            $new_array[$k] = $array[$k];
	        }
	    }
	    return $new_array;
	}
}