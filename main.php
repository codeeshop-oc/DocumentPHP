<?php

error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);

include 'vendor/autoload.php';

print_r('<center>================== Start ==================</center>');

$copy = new DocumentPHP\FetchFiles();
$copy->start_processing(getcwd() . '/catalog/controller/api/');
echo "<pre>";
print_r($copy->getResults());
echo "</pre>";

print_r('<center>================== Done ==================</center>');
?>