<?php
/**
 * Created by PhpStorm.
 * User: matthes
 * Date: 20.07.15
 * Time: 14:34
 */



require __DIR__ . "/../../src/TextTemplate.php";


$tt = new TextTemplate();


$tt->loadTemplate(file_get_contents(__DIR__ . "/input_large.txt"));

$startTime = microtime(true);
$text = $tt->apply([]);


echo "Time parsing: " . number_format(microtime(true) - $startTime, 3) . "[sec]";
