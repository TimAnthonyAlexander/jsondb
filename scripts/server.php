<?php

namespace scripts;

use src\JSDB;

require_once __DIR__ . '/../vendor/autoload.php';



while (true) {
    $tables = JSDB::getTableFolders();

    $objects = [];

    foreach ($tables as $table) {
        $objects[$table] = $objects[$table] ?? new JSDB($table);
        $objects[$table]->writeMerged();
    }

    sleep(1);
}
