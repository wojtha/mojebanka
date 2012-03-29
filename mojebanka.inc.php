<?php

define('MOJEBANKA_CELL_DIVIDER', '________________________________________________________________________________');

/**
 * Parse the txt content to transactions.
 *
 * @param $string string
 * @return array
 */
function mojebanka_txt_parse($string) {
  $transactions = array();
  $string = iconv('windows-1250','utf-8', $string);
  $cells = explode(MOJEBANKA_CELL_DIVIDER, $string);

  foreach ($cells as $cell) {
    $cell = trim($cell, " \r\n\t");

    if (strpos($cell, 'ČÍSLO ÚČTU : ') !== FALSE) {
      continue;
    }
    elseif (strpos($cell, 'Obrat na vrub') !== FALSE) {
      continue;
    }
    elseif (strpos($cell, 'Číslo protiúčtu                VS') !== FALSE) {
      continue;
    }
    elseif (strpos($cell, 'Transakční historie') !== FALSE) {
      continue;
    }
    elseif (strpos($cell, 'Za období      od') !== FALSE) {
      continue;
    }
    elseif (empty($cell)) {
      continue;
    }

    $matches = array();
    if (preg_match('~(\d*/\d{4})[ ]*(\d*)[ ]*(-?\+?\d+,\d{2}+ CZK)[ ]*(\d{2}\.\d{2}.\d{4})\r?\n(|Úhrada|Inkaso|Zahraniční platba IDK vyšlo|Poplatek IDK vyšlo)[ ]*(\d+)[ ]*(\d{2}\.\d{2}.\d{4})\r?\n(\d[0-9A-Z -]{14,31})[ ]*(\d+)[ ]*(\d{2}\.\d{2}.\d{4})\r?\nPopis příkazce[ ]*(.+)\r?\nPopis pro příjemce[ ]*(.+)\r?\nSystémový popis[ ]*(.+)~', $cell, $matches)) {

    //  Číslo protiúčtu                VS        Částka       Datum přijetí k zaúčtování
    //  Typ transakce                  KS        a měna                 Datum splatnosti
    //  ID transakce                   SS                               Datum zaúčtování
      $transaction = new stdClass();
      $transaction->account = $matches[1]; // 94-60576642/8060
      $transaction->var_sym = $matches[2]; // 8301290723
      $transaction->price = $matches[3]; // -1500,00 CZK
      $transaction->date1 = $matches[4]; // 08.09.2011
      $transaction->type = $matches[5]; // Úhrada
      $transaction->const_sym = $matches[6]; // 0
      $transaction->date2 = $matches[7]; // 08.09.2011
      $transaction->trans_id = $matches[8]; // 000-08092011 005-005-001596020
      $transaction->spec_s = $matches[9]; // 0
      $transaction->date3 = $matches[10]; //  08.09.2011
      $transaction->desc1 = preg_replace('~[ ]{2,}~', ' ', trim($matches[11], " \n\r\t")); //  STAVEBNI SPORENI - BURINKA
      $transaction->desc2 = preg_replace('~[ ]{2,}~', ' ', trim($matches[12], " \n\r\t")); //  NA   AC-0000940060576642
      $transaction->desc3 = preg_replace('~[ ]{2,}~', ' ', trim($matches[13], " \n\r\t")); //  Úhrada do jiné banky
      $transaction->desc4 = '';
      if ($start = strpos($cell, 'Zpráva pro příjemce')) {
        $desc4 = substr($cell, $start + 19);
        $desc4 = preg_replace('~\n|\r|\t~', ' ', $desc4);
        $transaction->desc4 = preg_replace('~[ ]{2,}~', ' ', $desc4);
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
    $data = '';
    $data .= 'D' . date('d/m/Y', $timestamp) . PHP_EOL;
    $data .= 'T' . number_format ($amount, 2) . PHP_EOL;
    $data .= 'P' . $payee . PHP_EOL;
    $data .= 'M' . $transaction->desc1 . ' ' . $transaction->desc2 . ' ' . $transaction->desc3 . ' ' . $transaction->desc4 . ' ' . PHP_EOL;
    fwrite($qif_file, $data . '^' . PHP_EOL);
  }

  fclose($qif_file);
}
