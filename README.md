# breathalyzer
Data centered test task

Additional comments: 
- on my PC (i7 3.5 GHz) it runs about 3.1-ish seconds on PHP 7.0.14, should run reasonably fast on most similar configurations;
- also tried some other approaches, like custom implementation of Levershtein Automaton in PHP and Trie implmentation in Python, none of these approaches proved fast enough;
- to maintain testability and other things the BreathalyzerWorker class could be split into multiple entities (for instance, VocabularyProvider, Vocabulary etc.), however system complexity was omitted on purpose in favor of brevity and simplicity;
- things that I would add first be it a production task - a) some caching for processed vocabulary; b) unit-tests;
