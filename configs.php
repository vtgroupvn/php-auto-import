<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'test_import');
define('DB_TABLE_IMPORT', 'new_rock_products_import');
define('CSV_FILENAME', 'productos_nrk.csv');
define('TOTAL_IMAGES', 11);
define('PRODUCT_BASEIMAGE_URL_200PX', 'http://200x.newrockplanet.com/');
define('PRODUCT_BASEIMAGE_URL_400PX', 'http://400x.newrockplanet.com/');
define('PRODUCT_BASEIMAGE_URL_1000PX', 'http://1000x.newrockplanet.com/');
/*
 * Field end of header line to make field default and remove \n||\r||\n\r
 * 
 * Default it is a string lower
 */
define('FIELD_END_OF_HEADERLINE', strtolower('tipo_tacon'));
/*
 * Charactor Distance of HEADER CSV
 */
define("EXPLODE_HEADER", ',');
//define("EXPLODE_HEADER", ';');
//define("EXPLODE_HEADER", '","');
//define("EXPLODE_HEADER", '";"');
/*
 * Charactor Distance of DATA CSV
 */
//define("EXPLODE_DATA", ',');
//define("EXPLODE_DATA", ';');
define("EXPLODE_DATA", '","');
//define("EXPLODE_DATA", '";"');
/*
 * Check or not check product image existing
 */
define("CHECK_PRODUCT_IMAGE_EXISTS", true);
?>