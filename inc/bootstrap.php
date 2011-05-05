<?php

define("INC", __DIR__);
define("DOCUMENTS", INC . "/../documents");

$db = new PDO("sqlite:" . INC . "/../db/documents.db");