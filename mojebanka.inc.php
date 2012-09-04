<?php

define('MOJEBANKA_CELL_DIVIDER', '____________________________________________________________________________________________________');

/**
 * Converts price to number format.
 *
 * Expected input is -d ddd,dd or -d.ddd,dd. Output is -dddd.dd.
 *
 * @param $price
 * @return mixed
 */
function mojebanka_convert_price($price) {
  return str_replace(' ', '', str_replace(',', '.', str_replace('.', ' ', $price)));
}

/**
 * Parse the txt content to transactions.
 *
 * @param $string string
 * @return array
 */
function mojebanka_txt_parse($string) {
  mb_internal_encoding('UTF-8');
  $transactions = array();
  //$string = iconv('windows-1250','utf-8', $string);
  $cells = explode(MOJEBANKA_CELL_DIVIDER, $string);

  foreach ($cells as $cell) {
    if (strpos($cell, "VÝPIS TRANSAKCÍ") !== FALSE || strpos($cell, "Vážená paní, vážený pane") !== FALSE) {
      continue;
    }

    $cell =  str_replace("\r\n", "\n", trim($cell, " \r\n\t"));
    $lines = explode("\n", $cell);
    $col0 = $col1 = $col2 = $col3 = $col4 = array();
    foreach ($lines as $line) {
      $col0[] = trim(mb_substr($line,  0, 17)); // 0
      $col1[] = trim(mb_substr($line, 17, 36)); // 1
      $col2[] = trim(mb_substr($line, 53, 23)); // 2
      $col3[] = trim(mb_substr($line, 76, 10)); // 3
      $col4[] = trim(mb_substr($line, 86, 14)); // 4
    }

    for ($i = 0; $i <= 4; ++$i) {
      $col = "col$i";
      $$col = array_values(array_filter($$col, 'strlen'));
    }

    // Just a quick guess if a transaction is valid.
    if (count($col3) == 3) {
      // |<----Col-0--->|<---------------Col-1-------------->|<--------Col-2-------->|<--Col-3-->|<--Col-4-->|
      // Datum zúčtování  Popis transakce                     Název protiúčtu        VS              Připsáno
      // Datum transakce  Identifikace transakce              Protiúčet a kód banky  KS              Odepsáno
      //                                                                             SS

      $transaction = new stdClass();
      $transaction->date1 = $col0[0]; // 20-08-2012
      $transaction->date2 = str_replace('-', '/', $col0[1]); // 20-08-2012
      $transaction->type = $col1[0]; // Platba na vrub vašeho účtu
      $transaction->trans_id = $col1[1]; // 000-08092011 005-005-001596020
      $transaction->var_sym = $col3[0]; // 8256546884
      $transaction->const_sym = $col3[1]; // 0
      $transaction->spec_s = $col3[2]; // 0
      $transaction->price = mojebanka_convert_price($col4[0]); // -1 500,00 or -1.500,00
      $transaction->account = ''; // 94-65078642/8060
      $transaction->desc1 = ''; //  STAVEBNI SPORENI - BURINKA
      $transaction->desc2 = ''; //  NA   AC-0000940060576642
      $transaction->desc3 = ''; //  Úhrada do jiné banky
      $transaction->desc4 = '';

      if (isset($col1[2])) {
        $transaction->desc3 = $col1[2];
        if (isset($col1[3])) {
          $transaction->desc3 .= ' ' . $col1[3];
        }
      }

      // Parse 2nd column
      foreach ($col2 as $line) {
        if (preg_match('~[0-9-]+\/[0-9]{4}~', $line)) {
          $transaction->account = $line;
        }
        elseif (preg_match('~NA AC-[0-9]+~', $line)) {
          $transaction->desc2 = $line;
        }
        else  {
          $transaction->desc1 .= ($transaction->desc1 ? ' ' : '') . $line;
        }
      }

      $transactions[] = $transaction;
    }
    else {
      // DEBUG
      print "\n\n=========================\nUnknown transaction!\n";
      print $cell;
    }
  }

  return $transactions;
}


/**
 * Save array of transactions to CSV file.
 */
function mojebanka_to_csv($transactions) {
  $filename = 'mojebanka_export_' . date('Y-m-d-H-i-s') . '.csv';
  $csv_file = fopen($filename, 'w+');
  $columns = array('date3', 'type', 'account', 'price', 'var_sym', 'desc1', 'desc2', 'desc3', 'desc4');
  fwrite($csv_file, implode("\t", $columns) . PHP_EOL);

  foreach ($transactions as $transaction) {
    $line = array();
    foreach ($columns as $key) {
      $line[] = $transaction->{$key};
    }
    fwrite($csv_file, implode("\t", $line) . PHP_EOL);
  }

  fclose($csv_file);
}


/**
 * Save array of transactions to QIF file.
 */
function mojebanka_to_qif($transactions) {
  $filename = 'mojebanka_export_' . date('Y-m-d-H-i-s') . '.qif';
  $qif_file = fopen($filename, 'w+');
  fwrite($qif_file, '!Type:Bank' . PHP_EOL);

  foreach ($transactions as $transaction) {
    $timestamp = strtotime($transaction->date1);
    $amount = preg_replace('~\+?(-?)(\d+),(\d{2}) CZK~', '$1$2.$3', $transaction->price);
    $payee = $transaction->account == '\0100' ? 'KB' : $transaction->account;
    if (!empty($transaction->var_sym)) {
      $payee .= ' ' . $transaction->var_sym;
    }
    $description = '';
    foreach (array('type', 'desc1', 'desc2', 'desc3', 'desc4') as $field) {
      if (!empty($transaction->{$field})) {
        $description .= ($description ? ' ' : '') . $transaction->{$field};
      }
    }
    $data = '';
    $data .= 'D' . date('d/m/Y', $timestamp) . PHP_EOL;
    $data .= 'T' . number_format($amount, 2) . PHP_EOL;
    $data .= 'P' . $payee . PHP_EOL;
    $data .= 'M' . $description . PHP_EOL;
    fwrite($qif_file, $data . '^' . PHP_EOL);
  }

  fclose($qif_file);
}
