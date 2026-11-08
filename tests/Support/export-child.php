<?php

$mode = isset($argv[1]) ? $argv[1] : '';
$root = dirname(dirname(__DIR__));

define('_PS_VERSION_', '1.7.6.0');
define('_DB_PREFIX_', 'ps_');
define('_PS_USE_SQL_SLAVE_', false);

require_once $root . '/tests/Support/PrestaShopStubs.php';
require_once $root . '/tests/Support/StatssalesFixtures.php';
require_once $root . '/statssales.php';

Db::ensureSchema();
Db::truncateAll();

$ctx = Context::getContext();
$ctx->employee->stats_date_from = '2020-06-01';
$ctx->employee->stats_date_to = '2020-06-30';

if ($mode === 'orders') {
    $ctx->employee->stats_date_from = '2020-06-15';
    $ctx->employee->stats_date_to = '2020-06-15';
    $o1 = StatssalesFixtures::insertOrder(array('invoice_date' => '2020-06-15 10:15:00', 'date_add' => '2020-06-15 10:15:00'));
    StatssalesFixtures::insertDetail($o1, 2);
    $o2 = StatssalesFixtures::insertOrder(array('invoice_date' => '2020-06-15 10:45:00', 'date_add' => '2020-06-15 10:45:00'));
    StatssalesFixtures::insertDetail($o2, 3);
    Tools::$values = array('export' => '1');
} elseif ($mode === 'sales') {
    $ctx->employee->stats_date_from = '2020-06-15';
    $ctx->employee->stats_date_to = '2020-06-15';
    $o = StatssalesFixtures::insertOrder(array(
        'invoice_date' => '2020-06-15 10:00:00',
        'date_add' => '2020-06-15 10:00:00',
        'total_paid_tax_excl' => 50,
    ));
    StatssalesFixtures::insertDetail($o, 1);
    Tools::$values = array('export' => '2');
} elseif ($mode === 'states') {
    StatssalesFixtures::insertStateLang(2, 7, 'Paid');
    StatssalesFixtures::insertStateLang(3, 7, 'Shipped');
  $addr = StatssalesFixtures::insertAddress(8);
    for ($i = 0; $i < 1; ++$i) {
        $oid = StatssalesFixtures::insertOrder(array(
            'id_address_delivery' => $addr,
            'invoice_date' => '2020-06-10 12:00:00',
            'date_add' => '2020-06-10 12:00:00',
        ));
        StatssalesFixtures::insertHistory($oid, 2, '2020-06-10 10:00:00');
    }
    for ($i = 0; $i < 3; ++$i) {
        $oid = StatssalesFixtures::insertOrder(array(
            'id_address_delivery' => $addr,
            'invoice_date' => '2020-06-11 12:00:00',
            'date_add' => '2020-06-11 12:00:00',
        ));
        StatssalesFixtures::insertHistory($oid, 3, '2020-06-11 10:00:00');
    }
    Tools::$values = array('export' => '3');
} else {
    fwrite(STDERR, 'unknown export mode');
    exit(2);
}

$module = new statssales();
$module->hookDisplayAdminStatsModules();
