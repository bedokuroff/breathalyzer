<?php
/**
 * @author Vitaliy Kalachikhin
 */
require_once(__DIR__.'/BreathalyzerWorker.php');

if (empty ($argv[1])) {
    die ("Error: please provide the input file with text to analyze. \n");
}
// this '3' offset seems enough to limit the vocabulary expanding mechanism for testing provided data
// if the number of changes in other data is wrong, this can be increased
$worker = new BreathalyzerWorker(3);
$worker->loadVocabulary(__DIR__.'/vocabulary.txt');
$worker->loadInput($argv[1]);
$changes = $worker->processText();
echo($changes."\n");