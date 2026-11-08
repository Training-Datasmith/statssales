<?php

date_default_timezone_set('UTC');

define('_PS_VERSION_', '1.7.6.0');
define('_DB_PREFIX_', 'ps_');
define('_PS_USE_SQL_SLAVE_', false);

$root = dirname(__DIR__);

require_once $root . '/tests/Support/PrestaShopStubs.php';
require_once $root . '/statssales.php';
require_once $root . '/upgrade/upgrade-2.1.0.php';
require_once $root . '/tests/Support/StatssalesTestCase.php';

Db::ensureSchema();
