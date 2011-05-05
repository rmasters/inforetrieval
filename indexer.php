#!/usr/bin/php
<?php

require_once __DIR__ . "/inc/bootstrap.php";

function index($path) {
    $content = file_get_contents($path);
    // Tokenise
    $tokens = preg_split("/[\s,\.;:\(\)]+/", $content);
    
    // Remove empty and non-content tokens and make array distinct
    $stoplist = array("the", "a", "an", "then", "there", "their", "they're");
    for ($i = 0; $i < count($tokens); $i++) {
        $tokens[$i] = trim($tokens[$i]);
        
        // Remove empty/non-content tokens
        if (strlen($tokens[$i]) == 0 || in_array($tokens[$i], $stoplist)) {
            unset($tokens[$i]);
            continue;
        }
        
        // Make distinct
        if (in_array($tokens[$i], array_slice($tokens, 0, $i))) {
            unset($tokens[$i]);
        }
    }
    $tokens = array_slice($tokens, 0);
    
    // Insert document and terms
    global $db;
    $title = basename($path);
    
    if (($pos = strpos($title, ".")) !== false) {
        $name = substr($title, 0, $pos);
        if (strlen($name) !== 0) {
            $title = $name;
        }
    }
    
    $documentStmt = $db->prepare("INSERT INTO document (title, document) VALUES (:title, :document)");
    $documentStmt->execute(array(":title" => $title, ":document" => $content));
    $documentStmt->closeCursor();
    
    $docId = $db->lastInsertId();
    
    $termStmt = $db->prepare("SELECT id FROM term WHERE term = :term");
    $insertTermStmt = $db->prepare("INSERT INTO term (term) VALUES (:term)");
    $docTermStmt = $db->prepare("INSERT INTO document_terms (term_id, document_id) VALUES (:termId, :docId)");
    for ($i = 0; $i < count($tokens); $i++) {
        $termStmt->execute(array(":term" => $tokens[$i]));
        
        $termId = 0;
        if (($row = $termStmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            $termId = $row["id"];
        } else {
            $insertTermStmt->execute(array(":term" => $tokens[$i]));
            $termId = $db->lastInsertId();
        }
        
        $docTermStmt->execute(array(":termId" => $termId, ":docId" => $docId));
    }
    
    $termStmt->closeCursor();
    $insertTermStmt->closeCursor();
    $docTermStmt->closeCursor();
}

if (count($argv) == 2) {
    // Index a given document
    if (!file_exists(/{$argv[1]})) {
        print "Document does not exist in " . DOCUMENTS;
        exit(1);
    }
    
    index(DOCUMENTS . "/{$argv[1]}");
} else { 
    // Index all documents under DOCUMENTS
    foreach (glob(DOCUMENTS . "/*") as $path) {
        index($path);
    }
}