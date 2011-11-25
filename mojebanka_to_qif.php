<?php

require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'parse_parameters.inc.php';
require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'mojebanka_to_qif.inc.php';

define('MOJEBANKA_CLI_HELP', <<<EOT
Mojebanka Txt Konverter
Konvertuje textovy vypis transakci do QIF nebo CVS formatu.

Pouziti:
php mojebanka_txt --format=qif Tisk_20110910153349.txt
php mojebanka_txt --format=cvs *.txt

Parametry:
--help         Napoveda - tento text.
--format=qif   Cilovy format: qif, cvs

EOT
);

/**
 * Parse command line parameters.
 */
function mojebanka_get_params() {
  $default_parameters = array(
    'format' => 'qif',
    'help' => '',
  );
  $parameters = parseParameters();
  array_shift($parameters); // remove script from params
  $parameters += $default_parameters;
  return $parameters;
}

/**
 * Parse files from command line parameters.
 */
function mojebanka_parse_files($parameters) {
  $files = array();
  foreach ($parameters as $key => $value) {
    if (is_numeric($key) && ($tmp_files = glob($value))) {
      $files = array_merge($tmp_files, $files);
    }
  }
  return array_unique($files);
}

//==============================================================================
// MAIN LOOP
//==============================================================================

$parameters = mojebanka_get_params();
$files = mojebanka_parse_files($parameters);

if (empty($files) || $parameters['help']) {
  print MOJEBANKA_CLI_HELP;
}

//print_r($parameters);
//print_r($files);

//$mojenbanka_txt = "Tisk_20110911000721.txt";
//$mojenbanka_txt = "Tisk_20110910153349.txt";
//$mojenbanka_txt = "Tisk_20110921113803.txt";
//$mojenbanka_txt = "Tisk_20110921115427.txt";
//$mojenbanka_txt = "Tisk_20111017192159.txt";
// $mojenbanka_txt = "Tisk_20111125110712.txt";
//
// $content = file_get_contents($mojenbanka_txt);
