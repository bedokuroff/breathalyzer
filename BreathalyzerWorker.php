<?php

/**
 * @author Vitaliy Kalachikhin
*/
class BreathalyzerWorker
{
    /**
     * Vocabulary processed with groups and needed structure
     *
     * @var array
     */
    private $extendedVocabulary;

    /**
     * Just an array of words, like provided in a vocabulary file
     *
     * @var array
     */
    private $rawVocabulary;

    /**
     * Input words
     *
     * @var array
     */
    private $inputArray;

    /**
     * Offset, roughly equals maximum Levenshtein distance / 2
     *
     * @var int
     */
    private $maxOffset;

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
        // this consumes more memory, but allows to avoid misses, no use to make it  greater
        // than the largest word
        $this->maxOffset = $maxLength;

        // we process the words here - we split them into the groups by length
        // each group contains words of some fixed length and arrays with words of
        // other lengths, based on $maxOffset (shorter and longer) for the search to be widened
        for ($i = 1; $i <= $maxLength; $i++) {
            $groups[0] = isset($words[$i]) ? $words[$i] : [];
            for ($j = 1; $j <= $this->maxOffset; $j++) {
                $groups[$j] = [];
                if (isset($words[$i - $j])) {
                    $groups[$j] = array_merge($groups[$j], $words[$i-$j]);
                }
                if (isset($words[$i + $j])) {
                    $groups[$j] = array_merge($groups[$j], $words[$i+$j]);
                }
            }
            $this->extendedVocabulary[$i] = $groups;
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

    public function processText()
    {
        if (empty($this->rawVocabulary)) {
            throw new \RuntimeException('No vocabulary loaded');
        }
        $changesCount = 0;
        $outputArray = [];
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
            // and here we go with the vocabulary search
            $outputArray[$inputWord] = $this->doLevenshteinCheck($inputWord);
            // we cache the changes amount for the word
            $changesCount += $outputArray[$inputWord];
        }

        return $changesCount;
    }

    /**
     * @param $inputWord
     * @return int|null
     */
    private function doLevenshteinCheck($inputWord)
    {
        $allowedDistance = 1; // we'll start from this value, and then expand if nothing is found in an iteration
        $minFoundDistance = 999; // just an orbitrary big value to initialize variable and avoid initializing in the loop below
        $inputLength = strlen($inputWord);
        // the special case - those really long words
        if ($inputLength > $this->maxOffset) {
            $allowedDistance = $inputLength - $this->maxOffset;
            $inputLength = $this->maxOffset - 1;
        }

        for ($i = 0; $i <= $this->maxOffset; $i++) {
           if (!empty ($this->extendedVocabulary[$inputLength][$i])) {
               foreach($this->extendedVocabulary[$inputLength][$i] as $vocabWord) {
                   $levDistance = levenshtein($inputWord, $vocabWord);
                   if ($levDistance === 1) {
                       return $levDistance; // the special case - the closest match, return it immediately
                   }
                   if ($levDistance < $minFoundDistance) {
                       $minFoundDistance = $levDistance;
                   }
               }
               // we don't want values which are at greater distance than allowed - maybe they will be found in next iterations
               if ($minFoundDistance <= $allowedDistance) {
                   return $minFoundDistance;
               }
           }
           $allowedDistance++;
        }

        // if nothing was found, we search the whole vocabulary without optimization, but this should not happen normally
        foreach ($this->rawVocabulary as $vocabWord) {
            $levDistance = levenshtein($inputWord, $vocabWord);
            if ($levDistance < $minFoundDistance) {
                $minFoundDistance = $levDistance;
            }
        }

        // if $this->maxOffset is high enough, this should not happen
        return $minFoundDistance;
    }
}