<?php

class StatssalesTotalsTest extends StatssalesTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->setEmployeeWindow('2020-06-01', '2020-06-30');
    }

    public function testEmptyPeriodTotalsAreNumericZeros()
    {
        $totals = $this->invokeGetTotals($this->newModule());
        $this->assertTrue(is_numeric($totals['orderCount']));
        $this->assertTrue(is_numeric($totals['orderSum']));
        $this->assertTrue(is_numeric($totals['products']));
        $this->assertMoney(0, $totals['orderSum']);
        $this->assertSame(0, (int) $totals['orderCount']);
        $this->assertSame(0, (int) $totals['products']);
    }

    public function testTotalsFollowValidLogableShopAndInvoiceWindow()
    {
        $a = StatssalesFixtures::insertOrder(array(
            'invoice_date' => '2020-06-10 12:00:00',
            'date_add' => '2020-01-01 00:00:00',
            'total_paid_tax_excl' => 40,
        ));
        StatssalesFixtures::insertDetail($a, 2);

        $b = StatssalesFixtures::insertOrder(array(
            'valid' => 0,
            'invoice_date' => '2020-06-10 12:00:00',
            'total_paid_tax_excl' => 1000,
        ));
        StatssalesFixtures::insertDetail($b, 7);

        StatssalesFixtures::insertOrder(array(
            'current_state' => 5,
            'logable' => 0,
            'invoice_date' => '2020-06-10 12:00:00',
            'total_paid_tax_excl' => 1000,
        ));

        $d = StatssalesFixtures::insertOrder(array(
            'id_shop' => 2,
            'invoice_date' => '2020-06-10 12:00:00',
            'total_paid_tax_excl' => 1000,
        ));
        StatssalesFixtures::insertDetail($d, 4);

        $e = StatssalesFixtures::insertOrder(array(
            'invoice_date' => '2020-05-31 23:59:59',
            'total_paid_tax_excl' => 1000,
        ));
        StatssalesFixtures::insertDetail($e, 4);

        $f = StatssalesFixtures::insertOrder(array(
            'invoice_date' => '2020-06-01 00:00:00',
            'date_add' => '2020-06-01 00:00:00',
            'total_paid_tax_excl' => 10,
        ));
        StatssalesFixtures::insertDetail($f, 1);

        $g = StatssalesFixtures::insertOrder(array(
            'invoice_date' => '2020-06-30 23:59:59',
            'date_add' => '2020-06-30 23:59:59',
            'total_paid_tax_excl' => 0.5,
        ));

        $totals = $this->invokeGetTotals($this->newModule());
        $this->assertSame(3, (int) $totals['orderCount']);
        $this->assertMoney(5050, $totals['orderSum']);
        $this->assertSame(3, (int) $totals['products']);
    }

    public function testCountryFilterLimitsTotals()
    {
        $a8 = StatssalesFixtures::insertAddress(8);
        $a9 = StatssalesFixtures::insertAddress(9);
        $p = StatssalesFixtures::insertOrder(array(
            'id_address_delivery' => $a8,
            'total_paid_tax_excl' => 15,
        ));
        StatssalesFixtures::insertDetail($p, 4);
        $q = StatssalesFixtures::insertOrder(array(
            'id_address_delivery' => $a9,
            'total_paid_tax_excl' => 30,
        ));
        StatssalesFixtures::insertDetail($q, 6);
        Tools::$values = array('id_country' => 8);
        $totals = $this->invokeGetTotals($this->newModule());
        $this->assertSame(1, (int) $totals['orderCount']);
        $this->assertMoney(1500, $totals['orderSum']);
        $this->assertSame(4, (int) $totals['products']);
    }

    public function testCountryRequestValueIsCastBeforeSql()
    {
        $a8 = StatssalesFixtures::insertAddress(8);
        $a9 = StatssalesFixtures::insertAddress(9);
        $p = StatssalesFixtures::insertOrder(array(
            'id_address_delivery' => $a8,
            'invoice_date' => '2020-06-10 12:00:00',
            'total_paid_tax_excl' => 15,
        ));
        StatssalesFixtures::insertDetail($p, 4);
        $q = StatssalesFixtures::insertOrder(array(
            'id_address_delivery' => $a9,
            'invoice_date' => '2020-06-10 12:00:00',
            'total_paid_tax_excl' => 30,
        ));
        StatssalesFixtures::insertDetail($q, 6);
        Tools::$values = array('id_country' => '8abc');
        $totals = $this->invokeGetTotals($this->newModule());
        $this->assertSame(1, (int) $totals['orderCount']);
        $this->assertMoney(1500, $totals['orderSum']);
        $probe = $this->newProbe();
        $probe->setOption('1-8abc', 2);
        $probe->probeGetData(2);
        $values = $probe->probeValues();
        $this->assertSame(1, (int) $values[0][10]);
    }

    public function testZeroSlipsAndOneSlipUseTheRefundFormula()
    {
        $n = StatssalesFixtures::insertOrder(array('total_paid_tax_excl' => 100));
        StatssalesFixtures::insertDetail($n, 1);
        $s = StatssalesFixtures::insertOrder(array(
            'total_paid_tax_excl' => 200,
            'conversion_rate' => 2,
        ));
        StatssalesFixtures::insertDetail($s, 1);
        StatssalesFixtures::insertSlip($s, 50, 10, 2);
        $totals = $this->invokeGetTotals($this->newModule());
        $this->assertSame(2, (int) $totals['orderCount']);
        $this->assertMoney(18000, $totals['orderSum']);
        $this->assertSame(2, (int) $totals['products']);
    }

    public function testTwoSlipsDoNotMultiplyTheOrderAmount()
    {
        $this->setEmployeeWindow('2020-06-15', '2020-06-15');
        $o = StatssalesFixtures::insertOrder(array(
            'invoice_date' => '2020-06-15 10:00:00',
            'date_add' => '2020-06-15 10:00:00',
            'total_paid_tax_excl' => 100,
        ));
        StatssalesFixtures::insertDetail($o, 4);
        StatssalesFixtures::insertSlip($o, 10, 0, 1);
        StatssalesFixtures::insertSlip($o, 15, 5, 1);
        $totals = $this->invokeGetTotals($this->newModule());
        $this->assertSame(1, (int) $totals['orderCount']);
        $this->assertMoney(8000, $totals['orderSum']);
        $this->assertSame(4, (int) $totals['products']);
        $probe = $this->newProbe();
        $probe->setOption('2-0', 1);
        $probe->probeGetData(1);
        $values = $probe->probeValues();
        $this->assertInternalType('string', $values[10]);
        $this->assertSame('80.00', $values[10]);
        $this->assertInternalType('string', $values[8]);
        $this->assertSame('0.00', $values[8]);
    }
}
