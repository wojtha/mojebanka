<?php

require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'parse_parameters.inc.php';
require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'mojebanka_to_qif.inc.php';

$default_parameters = array(
  'format' => 'qif',
  'help' => '',
);
$parameters = parseParameters();
array_shift($parameters); // remove script from params
$parameters += $default_parameters;
//print_r($parameters);

//$mojenbanka_txt = "Tisk_20110911000721.txt";
//$mojenbanka_txt = "Tisk_20110910153349.txt";
//$mojenbanka_txt = "Tisk_20110921113803.txt";
//$mojenbanka_txt = "Tisk_20110921115427.txt";
//$mojenbanka_txt = "Tisk_20111017192159.txt";
// $mojenbanka_txt = "Tisk_20111125110712.txt";
//
// $content = file_get_contents($mojenbanka_txt);
