<?php

/**
 * PHPUnit bootstrap: load Composer autoloader, then ensure the contract-test
 * database schema exists (imported from installation/backup/database.sql).
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/bootstrap/import_test_db.php';
