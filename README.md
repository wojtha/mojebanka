# Mojebanka konverter transakcí

Konvertuje textový výpis transakcí z TXT do QIF nebo CSV formátu.

Určeno zejména pro ty, kteří mají obyčejné internetové bankovnictví u Komerční
banky, kde je povolen export transakcí pouze do textového formátu.

## Použití

php mojebanka.php --format=qif Tisk_20110910153349.txt
php mojebanka.php --format=csv *.txt

## Parametry

 * --help         Nápověda - tento text.
 * --format=qif   Cílový formát: qif, csv

## Licence

Dual licensed under Apache License 2.0 and GNU GPL 2.0.
