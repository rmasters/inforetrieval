# Information Retrieval demonstration

This project is a working demonstration of what I have learned in my second
year information retrieval module.  It features an indexer and a searching
utility with its own query selector syntax.

The project is written in PHP 5.3, but parts will be re-written in more
suitable languages later (either all Python or the front-end might stay as 
PHP - undecided yet).

## Example usage

### Indexing documents

    # Index all documents under /documents
    # Titles will be the filename
    ./indexer.php
    # Index a specific document, relative to the directory you are calling from
    ./indexer.php document.txt
    # Todo: supply attributes alternatively
    #./indexer.php document.txt title:"Alternative title" attribute:value

### Searching documents

    # Search for a basic term
    ./search.php information
    # Search for multiple terms (contains "information" or "retrieval")
    ./search.php information retrieval
    # Todo: search for phrases
    #./search.php "information retrieval"
    # Todo: term weighting
    #./search.php +information -retrieval