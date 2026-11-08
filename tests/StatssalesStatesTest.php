<?php

class StatssalesStatesTest extends StatssalesTestCase
{
    private function statesProbe()
    {
        $this->setEmployeeWindow('2020-06-01', '2020-06-30');
        $probe = $this->newProbe();
        $probe->setEmployee(1);
        $probe->probeSetLang(7);

        return $probe;
    }

    private function mapLegendValues(StatssalesProbe $probe)
    {
        $legend = $probe->probeLegend();
        $values = $probe->probeValues();
        $this->assertSame(count($legend), count($values));
        $map = array();
        foreach ($legend as $i => $name) {
            $map[$name] = $values[$i];
        }

        return $map;
    }

    public function testPieUsesLatestHistoryInTheRequestedLanguage()
    {
        StatssalesFixtures::insertStateLang(2, 7, 'Paid');
        StatssalesFixtures::insertStateLang(2, 1, 'Paye');
        StatssalesFixtures::insertStateLang(3, 7, 'Shipped');

        $l = StatssalesFixtures::insertOrder(array(
            'date_add' => '2020-06-05 12:00:00',
            'invoice_date' => '2020-06-05 12:00:00',
        ));
        StatssalesFixtures::insertHistory($l, 2, '2020-06-01 10:00:00');
        StatssalesFixtures::insertHistory($l, 3, '2020-06-02 11:00:00');

        $m = StatssalesFixtures::insertOrder(array(
            'date_add' => '2020-06-06 12:00:00',
            'invoice_date' => '2020-06-06 12:00:00',
        ));
        StatssalesFixtures::insertHistory($m, 2, '2020-06-03 10:00:00');

        $n = StatssalesFixtures::insertOrder(array(
            'date_add' => '2020-05-01 12:00:00',
            'invoice_date' => '2020-06-04 12:00:00',
        ));
        StatssalesFixtures::insertHistory($n, 2, '2020-06-04 10:00:00');

        $probe = $this->statesProbe();
        $probe->setOption('3-0', 1);
        $probe->probeGetData(1);
        $map = $this->mapLegendValues($probe);
        $this->assertArrayHasKey('Shipped', $map);
        $this->assertArrayHasKey('Paid', $map);
        $this->assertTrue(is_numeric($map['Shipped']));
        $this->assertTrue(is_numeric($map['Paid']));
        $this->assertSame(1, (int) $map['Shipped']);
        $this->assertSame(1, (int) $map['Paid']);
        $this->assertArrayNotHasKey('Paye', $map);
    }

    public function testPieWindowIsOrderDateAdd()
    {
        StatssalesFixtures::insertStateLang(2, 7, 'Paid');
        StatssalesFixtures::insertStateLang(3, 7, 'Shipped');

        $a = StatssalesFixtures::insertOrder(array(
            'date_add' => '2020-06-10 12:00:00',
            'invoice_date' => '2019-01-01 12:00:00',
        ));
        StatssalesFixtures::insertHistory($a, 2, '2020-06-10 10:00:00');

        $b = StatssalesFixtures::insertOrder(array(
            'date_add' => '2019-06-10 12:00:00',
            'invoice_date' => '2020-06-10 12:00:00',
        ));
        StatssalesFixtures::insertHistory($b, 3, '2019-06-10 10:00:00');

        $probe = $this->statesProbe();
        $probe->setOption('3-0', 1);
        $probe->probeGetData(1);
        $map = $this->mapLegendValues($probe);
        $this->assertArrayHasKey('Paid', $map);
        $this->assertSame(1, (int) $map['Paid']);
        $this->assertCount(1, $map);
    }

    public function testPieCountryFilter()
    {
        StatssalesFixtures::insertStateLang(2, 7, 'Paid');
        StatssalesFixtures::insertStateLang(3, 7, 'Shipped');
        $a8 = StatssalesFixtures::insertAddress(8);
        $a9 = StatssalesFixtures::insertAddress(9);

        $o8 = StatssalesFixtures::insertOrder(array(
            'id_address_delivery' => $a8,
            'date_add' => '2020-06-10 12:00:00',
            'invoice_date' => '2020-06-10 12:00:00',
        ));
        StatssalesFixtures::insertHistory($o8, 2, '2020-06-10 10:00:00');

        $o9 = StatssalesFixtures::insertOrder(array(
            'id_address_delivery' => $a9,
            'date_add' => '2020-06-11 12:00:00',
            'invoice_date' => '2020-06-11 12:00:00',
        ));
        StatssalesFixtures::insertHistory($o9, 3, '2020-06-11 10:00:00');

        $probe = $this->statesProbe();
        $probe->setOption('3-8', 1);
        $probe->probeGetData(1);
        $map = $this->mapLegendValues($probe);
        $this->assertArrayHasKey('Paid', $map);
        $this->assertSame(1, (int) $map['Paid']);
        $this->assertCount(1, $map);
    }
}
