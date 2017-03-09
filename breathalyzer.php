<?php
/**
 * @author Vitaliy Kalachikhin
 */
require_once(__DIR__.'/BreathalyzerWorker.php');

if (empty ($argv[1])) {
    die ("Error: please provide the input file with text to analyze. \n");
}

$worker = new BreathalyzerWorker();
$worker->loadVocabulary(__DIR__.'/vocabulary.txt');
$worker->loadInput($argv[1]);
$changes = $worker->processText();
echo($changes."\n");