<?php

/**
 * @author Vitaliy Kalachikhin
*/
class BreathalyzerWorker
{
    /**
     * @var array
     */
    private $extendedVocabulary;

    /**
     * @var array
     */
    private $rawVocabulary;

    /**
     * @var array
     */
    private $inputArray;

    /**
     * @var int
     */
    private $maxDistance;

    /**
     * @param int $maxDistance Maximum Levenshtein distance
     */
    public function __construct($maxDistance)
    {
        $this->maxDistance = $maxDistance;
    }

    /**
     * @param string $filename Path to file with vocabulary
     */
    public function loadVocabulary($filename)
    {
        $handle = fopen($filename, 'rb');
        $words = [];
        $maxLength = 0;

        while (($line = stream_get_line($handle, 0, "\n")) !== false) {
            $lineLength = strlen($line);
            $words[$lineLength][] = $this->rawVocabulary[] = $line;
            if ($lineLength > $maxLength) {
                $maxLength = $lineLength;
            }
        }
        fclose($handle);

        for ($i = 1; $i < $maxLength; $i++) {
            $groupedArray = array_merge(
                isset($words[$i]) ? $words[$i] : [],
                isset($words[$i - 1]) ? $words[$i - 1] : [],
                isset($words[$i + 1]) ? $words[$i + 1] : [],
                isset($words[$i - 2]) ? $words[$i - 2] : [],
                isset($words[$i + 2]) ? $words[$i + 2] : []
            );
            foreach ($groupedArray as $item) {
                $this->extendedVocabulary[$i][$item[0]][] = $item;
            }
        }
    }

    /**
     * @param string $filename Path to file with input data
     */
    public function loadInput($filename)
    {
        $inputString = strtoupper(file_get_contents($filename));
        $this->inputArray = preg_split('/\s+/', trim($inputString));
    }

    /**
     * Main processing function
     */
    public function processText()
    {
        if (empty($this->rawVocabulary)) {
            throw new \RuntimeException('No vocabulary loaded');
        }
        $changesCount = 0;
        $outputArray = [];
        $start = microtime(true);
        foreach ($this->inputArray as $inputWord) {
            // maybe it is cached already?
            if (isset($outputArray[$inputWord])) {
                $changesCount += $outputArray[$inputWord];
                continue;
            }
            // first step - we do the trivial check, if word exists in a vocabulary, everything is fine, no changes needed
            if (in_array($inputWord, $this->rawVocabulary)) {
                $outputArray[$inputWord] = 0;
                continue;
            }
            // and here we go with the vocabulary
            $outputArray[$inputWord] = $this->doLevenshteinCheck($inputWord);
            // we cache the changes amount for the word
            $changesCount += $outputArray[$inputWord];
        }

        $end = microtime(true) - $start;
        echo 'Execution time: '.$end.' seconds';
        echo "\n";
        echo 'Changes done: '.$changesCount;
        echo "\n";
    }

    private function doLevenshteinCheck($inputWord)
    {
        $minDistance = $this->maxDistance + 1; // initialize - just to be sure we won't get value larger than that
        $inputLength = strlen($inputWord);
        $firstLetter = $inputWord[0];
        // we optimize the only optimizable thing here - we allow the cycle to find the closest match (distance = 1) as fast as possible
        // first of all we search through the words beginning with the same letter (improvement - 0.2-0.3 seconds here)
        foreach($this->extendedVocabulary[$inputLength][$firstLetter] as $vocabWord) {
            $levDistance = levenshtein($inputWord, $vocabWord);
            if ($levDistance == 1) { // this is a minimum possible result (closest match), no need to look further
                return $levDistance;
            }
        }

        // then we search throught the rest of letters
        foreach ($this->extendedVocabulary[$inputLength] as $letter => $letterArray) {
            if ($letter === $firstLetter) {
                continue;
            }
            foreach ($letterArray as $vocabWord) {
                $levDistance = levenshtein($inputWord, $vocabWord);
                if ($levDistance === 1) { // this is a minimum possible result (closest match), no need to look further
                    return $levDistance;
                }
                if ($levDistance < $minDistance) {
                    $minDistance = $levDistance;
                }
            }
        }
        return $minDistance;
    }
}