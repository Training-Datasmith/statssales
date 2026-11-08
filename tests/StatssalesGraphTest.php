<?php

class StatssalesGraphTest extends StatssalesTestCase
{
    private function graphProbe()
    {
        $probe = $this->newProbe();
        $probe->setEmployee(1);
        $probe->probeSetLang(7);

        return $probe;
    }

    public function testOption1BucketsOrdersAndProductsByHour()
    {
        $this->setEmployeeWindow('2020-06-15', '2020-06-15');
        $o1 = StatssalesFixtures::insertOrder(array('invoice_date' => '2020-06-15 10:15:00'));
        StatssalesFixtures::insertDetail($o1, 2);
        StatssalesFixtures::insertDetail($o1, 3);
        StatssalesFixtures::insertSlip($o1, 1, 0, 1);
        $o2 = StatssalesFixtures::insertOrder(array('invoice_date' => '2020-06-15 10:45:00'));
        StatssalesFixtures::insertDetail($o2, 1);
        $o3 = StatssalesFixtures::insertOrder(array('invoice_date' => '2020-06-15 11:00:00'));
        StatssalesFixtures::insertDetail($o3, 4);
        $out = StatssalesFixtures::insertOrder(array('invoice_date' => '2020-06-14 10:15:00'));
        StatssalesFixtures::insertDetail($out, 9);

        $probe = $this->graphProbe();
        $probe->setOption('1-0', 2);
        $probe->probeGetData(2);
        $v = $probe->probeValues();
        $this->assertCount(24, $v[0]);
        $this->assertInternalType('int', $v[0][10]);
        $this->assertSame(2, $v[0][10]);
        $this->assertInternalType('int', $v[1][10]);
        $this->assertSame(6, $v[1][10]);
        $this->assertSame(1, $v[0][11]);
        $this->assertSame(4, $v[1][11]);
        $this->assertSame(0, $v[0][9]);
        $this->assertSame(0, $v[1][9]);
        $this->assertSame(0, $v[0][15]);
        $legend = $probe->probeLegend();
        $this->assertSame('10h', $legend[10]);
        $this->assertSame('', $legend[11]);
    }

    public function testOption1BucketsByDayOfMonth()
    {
        $this->setEmployeeWindow('2020-06-01', '2020-06-20');
        $o1 = StatssalesFixtures::insertOrder(array('invoice_date' => '2020-06-04 08:00:00'));
        StatssalesFixtures::insertDetail($o1, 2);
        $o2 = StatssalesFixtures::insertOrder(array('invoice_date' => '2020-06-04 18:00:00'));
        StatssalesFixtures::insertDetail($o2, 5);
        $o3 = StatssalesFixtures::insertOrder(array('invoice_date' => '2020-06-20 12:00:00'));
        StatssalesFixtures::insertDetail($o3, 1);
        $o4 = StatssalesFixtures::insertOrder(array('invoice_date' => '2020-06-21 12:00:00'));
        StatssalesFixtures::insertDetail($o4, 8);

        $probe = $this->graphProbe();
        $probe->setOption('1-0', 2);
        $probe->probeGetData(2);
        $v = $probe->probeValues();
        $this->assertSame(2, $v[0][4]);
        $this->assertSame(7, $v[1][4]);
        $this->assertSame(1, $v[0][20]);
        $this->assertSame(0, $v[0][1]);
        $this->assertCount(20, $v[0]);
    }

    public function testOption1BucketsByMonth()
    {
        $this->setEmployeeWindow('2020-03-01', '2020-08-31');
        $o1 = StatssalesFixtures::insertOrder(array('invoice_date' => '2020-06-15 12:00:00'));
        StatssalesFixtures::insertDetail($o1, 2);
        $o2 = StatssalesFixtures::insertOrder(array('invoice_date' => '2020-03-02 12:00:00'));
        StatssalesFixtures::insertDetail($o2, 1);
        $o3 = StatssalesFixtures::insertOrder(array('invoice_date' => '2020-02-15 12:00:00'));
        StatssalesFixtures::insertDetail($o3, 9);

        $probe = $this->graphProbe();
        $probe->setOption('1-0', 2);
        $probe->probeGetData(2);
        $v = $probe->probeValues();
        $this->assertSame(1, $v[0][6]);
        $this->assertSame(2, $v[1][6]);
        $this->assertSame(1, $v[0][3]);
        $this->assertSame(0, $v[0][4]);
        $this->assertCount(6, $v[0]);
    }

    public function testOption1BucketsByYear()
    {
        $this->setEmployeeWindow('2018-01-01', '2020-12-31');
        $o1 = StatssalesFixtures::insertOrder(array('invoice_date' => '2019-07-01 12:00:00'));
        StatssalesFixtures::insertDetail($o1, 3);
        $o2 = StatssalesFixtures::insertOrder(array('invoice_date' => '2018-01-01 12:00:00'));
        StatssalesFixtures::insertDetail($o2, 1);
        $o3 = StatssalesFixtures::insertOrder(array('invoice_date' => '2017-12-31 12:00:00'));
        StatssalesFixtures::insertDetail($o3, 5);
        $o4 = StatssalesFixtures::insertOrder(array('invoice_date' => '2021-01-01 12:00:00'));
        StatssalesFixtures::insertDetail($o4, 2);

        $probe = $this->graphProbe();
        $probe->setOption('1-0', 2);
        $probe->probeGetData(2);
        $v = $probe->probeValues();
        $this->assertSame(1, $v[0][2019]);
        $this->assertSame(3, $v[1][2019]);
        $this->assertSame(1, $v[0][2018]);
        $this->assertSame(0, $v[0][2020]);
        $this->assertCount(3, $v[0]);
    }

    public function testOption2FormatsNetSalesWithThousandsSeparator()
    {
        $this->setEmployeeWindow('2020-06-15', '2020-06-15');
        $o1 = StatssalesFixtures::insertOrder(array('invoice_date' => '2020-06-15 10:00:00', 'total_paid_tax_excl' => 1000));
        StatssalesFixtures::insertDetail($o1, 1);
        $o2 = StatssalesFixtures::insertOrder(array('invoice_date' => '2020-06-15 10:30:00', 'total_paid_tax_excl' => 234.5));
        StatssalesFixtures::insertDetail($o2, 1);
        $o3 = StatssalesFixtures::insertOrder(array(
            'invoice_date' => '2020-06-15 11:00:00',
            'total_paid_tax_excl' => 200,
            'conversion_rate' => 2,
        ));
        StatssalesFixtures::insertDetail($o3, 1);
        StatssalesFixtures::insertSlip($o3, 50, 10, 2);

        $probe = $this->graphProbe();
        $probe->setOption('2-0', 1);
        $probe->probeGetData(1);
        $v = $probe->probeValues();
        $this->assertInternalType('string', $v[10]);
        $this->assertSame('1,234.50', $v[10]);
        $this->assertInternalType('string', $v[11]);
        $this->assertSame('80.00', $v[11]);
    }

    public function testOption2FormatsEmptyHoursAsZeroMoney()
    {
        $this->setEmployeeWindow('2020-06-15', '2020-06-15');
        $o = StatssalesFixtures::insertOrder(array(
            'invoice_date' => '2020-06-15 10:00:00',
            'total_paid_tax_excl' => 50,
        ));
        StatssalesFixtures::insertDetail($o, 1);
        $probe = $this->graphProbe();
        $probe->setOption('2-0', 1);
        $probe->probeGetData(1);
        $v = $probe->probeValues();
        $this->assertSame('50.00', $v[10]);
        $this->assertSame('0.00', $v[8]);
    }

    public function testGraphCountryAndShopFilters()
    {
        $this->setEmployeeWindow('2020-06-01', '2020-06-30');
        $a8 = StatssalesFixtures::insertAddress(8);
        $a9 = StatssalesFixtures::insertAddress(9);
        $o1 = StatssalesFixtures::insertOrder(array(
            'id_address_delivery' => $a8,
            'invoice_date' => '2020-06-10 12:00:00',
        ));
        StatssalesFixtures::insertDetail($o1, 2);
        $o2 = StatssalesFixtures::insertOrder(array(
            'id_address_delivery' => $a9,
            'invoice_date' => '2020-06-10 12:00:00',
        ));
        StatssalesFixtures::insertDetail($o2, 5);
        $o3 = StatssalesFixtures::insertOrder(array(
            'id_shop' => 2,
            'id_address_delivery' => $a8,
            'invoice_date' => '2020-06-10 12:00:00',
        ));
        StatssalesFixtures::insertDetail($o3, 7);

        $probe = $this->graphProbe();
        $probe->setOption('1-8', 2);
        $probe->probeGetData(2);
        $v = $probe->probeValues();
        $this->assertSame(1, $v[0][10]);
        $this->assertSame(2, $v[1][10]);
    }

    public function testOption1KeepsOrderAndSumsDetailsWhenASlipExists()
    {
        $this->setEmployeeWindow('2020-06-15', '2020-06-15');
        $o = StatssalesFixtures::insertOrder(array(
            'invoice_date' => '2020-06-15 10:00:00',
            'total_paid_tax_excl' => 100,
        ));
        StatssalesFixtures::insertDetail($o, 2);
        StatssalesFixtures::insertDetail($o, 4);
        StatssalesFixtures::insertSlip($o, 10, 0, 1);
        $probe = $this->graphProbe();
        $probe->setOption('1-0', 2);
        $probe->probeGetData(2);
        $v = $probe->probeValues();
        $this->assertSame(1, $v[0][10]);
        $this->assertSame(6, $v[1][10]);
    }
}
