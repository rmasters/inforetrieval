#!/usr/bin/php
<?php

/*
term: (id, term)
document: (id, title, document)
document_terms: (term_id, document_id)
*/

require_once __DIR__ . "/inc/bootstrap.php";

if (count($argv) == 1) {
    print "Please supply a search term ('search.php term')";
    exit(1);
}

$query = trim(implode(" ", array_slice($argv, 1)));

/*
a b c ((a and b and c) or (a and b) or (b and c) or (a and c) or (a) or (b) or (c))
a +b -c (((b and a) or (b)) and (!c))
*/
if (strlen($query) == 0) {
    print "Please supply a search term ('search.php term')";
}

$parts = explode(" ", $query);

// First get term ids
$where = "";
for ($i = 0; $i < count($parts); $i++) {
    $part = $db->quote(trim($parts[$i], "+-"));
    $where .= "term = $part";
    if ($i < count($parts) - 1) {
        $where .= " OR ";
    }
}

$termIdStmt = $db->prepare("SELECT * FROM term WHERE $where");
$termIdStmt->execute();
$termIdsA = $termIdStmt->fetchAll(PDO::FETCH_ASSOC);
$termIdStmt->closeCursor();

// Restructure termIdsA from {term: term, id: id} to (term: id)
$termIds = array();
foreach ($termIdsA as $i => $term) {
    $termIds[$term["term"]] = $term["id"];
}

// Trim out terms that don't exist
for ($i = 0; $i < count($parts); $i++) {
    if (!isset($termIds[$parts[$i]])) {
        unset($parts[$i]);
    }
}
$parts = array_slice($parts, 0);

// Formulate query
$where = "";
for ($i = 0; $i < count($parts); $i++) {
    $where .= "(";
    for ($j = 0; $j <= $i; $j++) {
        $where .= "term_id = {$termIds[$parts[$j]]}";
        if ($j <= $i - 1) {
            $where .= " AND ";
        }
    }
    $where .= ")";
    if ($i < count($parts) - 1) {
        $where .= " OR ";
    }
}

// Terms not in index
if (strlen(trim($where, "()")) == 0) {
    print "No results for those terms";
    exit;
}

// Search
$searchStmt = $db->prepare("SELECT document.id, document.title FROM document_terms JOIN document ON document.id = document_terms.document_id WHERE $where");
$searchStmt->execute();

print "Results for $query\n";
while ($row = $searchStmt->fetch(PDO::FETCH_ASSOC)) {
    print "#{$row["id"]}: {$row["title"]}\n";
}