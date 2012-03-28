<?php

require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'parse_parameters.inc.php';
require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'mojebanka.inc.php';

define('MOJEBANKA_CLI_HELP', <<<EOT
Mojebanka - konvertor transakci 
Konvertuje textovy vypis transakci z TXT do QIF nebo CSV formatu.

Pouziti:
php mojebanka.php Tisk_20110910153349.txt
php mojebanka.php --format=csv *.txt

Parametry:
--help         Napoveda - tento text.
--format=qif   Cilovy format: qif, csv

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
$format = strtolower($parameters['format']);
$formatter = 'mojebanka_to_' . $format;

if (empty($files) || $parameters['help']) {
  print MOJEBANKA_CLI_HELP;
}
elseif (!function_exists($formatter)) {
  print "Chyba: Neplatny format $format.\n\n";
  print MOJEBANKA_CLI_HELP;
}
else {
  foreach ($files as $file) {
    // 1. get content
    $txt = file_get_contents($file);
    // 2. parse
    $transactions = mojebanka_txt_parse($txt);
    // 3. save to file
    call_user_func($formatter, $transactions);
  }
}

//DEBUG
//print_r($parameters);
//print_r($files);
