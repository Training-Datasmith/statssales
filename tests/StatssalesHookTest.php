<?php

class StatssalesHookTest extends StatssalesTestCase
{
    private function selectedOptionValues($html)
    {
        $doc = new DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $doc->loadHTML('<div>' . $html . '</div>');
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        $selected = array();
        foreach ($doc->getElementsByTagName('option') as $opt) {
            if ($opt->hasAttribute('selected')) {
                $selected[] = $opt->getAttribute('value');
            }
        }

        return $selected;
    }

    public function testAllCountriesIsTheOnlySelectionWhenCountryIsAbsent()
    {
        $o = StatssalesFixtures::insertOrder(array());
        StatssalesFixtures::insertDetail($o, 1);
        $html = $this->newModule()->hookDisplayAdminStatsModules();
        $selected = $this->selectedOptionValues($html);
        $this->assertCount(1, $selected);
        $this->assertSame('0', $selected[0]);
        $this->assertContains('France', $html);
        $this->assertNotContains('Frankreich', $html);
    }

    public function testFilteredCountryIsTheOnlySelectedOption()
    {
        $o = StatssalesFixtures::insertOrder(array());
        StatssalesFixtures::insertDetail($o, 1);
        Tools::$values = array('id_country' => 9);
        $html = $this->newModule()->hookDisplayAdminStatsModules();
        $selected = $this->selectedOptionValues($html);
        $this->assertCount(1, $selected);
        $this->assertSame('9', $selected[0]);
        $this->assertNotContains('value="0" selected="selected"', $html);
    }

    public function testHookRendersTotalsPriceAndEngineArguments()
    {
        $addr = StatssalesFixtures::insertAddress(8);
        $o = StatssalesFixtures::insertOrder(array(
            'id_address_delivery' => $addr,
            'total_paid_tax_excl' => 40,
            'invoice_date' => '2020-06-10 12:00:00',
            'date_add' => '2020-06-10 12:00:00',
        ));
        StatssalesFixtures::insertDetail($o, 2);
        $html = $this->newModule()->hookDisplayAdminStatsModules();
        $doc = new DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $doc->loadHTML('<div>' . $html . '</div>');
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        $stats = array();
        foreach ($doc->getElementsByTagName('span') as $span) {
            if ($span->getAttribute('class') === 'totalStats') {
                $stats[] = $span->textContent;
            }
        }
        $this->assertSame(array('1', '2'), $stats);
        $this->assertCount(1, LocaleSpy::$calls);
        $this->assertTrue(is_numeric(LocaleSpy::$calls[0][0]));
        $this->assertMoney(4000, LocaleSpy::$calls[0][0]);
        $this->assertSame('EUR', LocaleSpy::$calls[0][1]);
        $this->assertContains('PRICE:EUR:40.00', $html);
        $this->assertContains('Sales and orders', $html);
        $this->assertContains('Only valid orders are graphically represented.', $html);
        $this->assertContains('shop\'s', $html);
        $this->assertNotContains('shop&#039;s', $html);
        $this->assertCount(3, Module::$engineCalls);
        $this->assertSame(array(
            'type' => 'line',
            'option' => '1-0',
            'layers' => 2,
        ), Module::$engineCalls[0]);
        $this->assertSame(array(
            'type' => 'line',
            'option' => '2-0',
        ), Module::$engineCalls[1]);
        $this->assertSame(array(
            'type' => 'pie',
            'option' => '3-0',
        ), Module::$engineCalls[2]);
        $this->assertContains('ENGINE:line:1-0:layers=2', $html);
        $this->assertContains('ENGINE:line:2-0:layers=1', $html);
        $this->assertContains('ENGINE:pie:3-0:layers=1', $html);
        $this->assertContains('name="submitCountry"', $html);
    }

    public function testHookSkipsPieEngineWhenOrderCountIsZero()
    {
        $html = $this->newModule()->hookDisplayAdminStatsModules();
        $this->assertContains('No orders for this period.', $html);
        $this->assertCount(2, Module::$engineCalls);
        $this->assertNotContains('ENGINE:pie:', $html);
    }

    public function testFormAndExportLinksEscapeTheRequestUri()
    {
        $_SERVER['REQUEST_URI'] = '/stats?q="x"';
        $o = StatssalesFixtures::insertOrder(array());
        StatssalesFixtures::insertDetail($o, 1);
        $html = $this->newModule()->hookDisplayAdminStatsModules();
        $this->assertContains('action="/stats?q=&quot;x&quot;"', $html);
        $this->assertNotContains('q="x"', $html);
        $this->assertContains('&amp;export=1', $html);
        $this->assertContains('&amp;export=2', $html);
        $this->assertContains('&amp;export=3', $html);
    }

    public function testExport1ChildWritesOrdersCsvAndExits()
    {
        $child = dirname(__DIR__) . '/tests/Support/export-child.php';
        list($exit, $stdout, $stderr) = $this->runPhpChild(array($child, 'orders'));
        $this->assertSame(0, $exit);
        $this->assertDiagnosticFreeOutput($stdout, $stderr);
        $this->assertContains('10h;2;5;', $stdout);
        $this->assertContains('08h;0;0;', $stdout);
        $this->assertNotContains('panel-heading', $stdout);
    }

    public function testExport2ChildWritesFormattedSalesCsvAndExits()
    {
        $child = dirname(__DIR__) . '/tests/Support/export-child.php';
        list($exit, $stdout, $stderr) = $this->runPhpChild(array($child, 'sales'));
        $this->assertSame(0, $exit);
        $this->assertDiagnosticFreeOutput($stdout, $stderr);
        // ModuleGraph::csvExport divides numeric-looking buckets, so 50.00 becomes 50 in CSV.
        $this->assertContains('10h;50;', $stdout);
        $this->assertContains('08h;0;', $stdout);
        $this->assertNotContains('10h;50.00;', $stdout);
        $this->assertNotContains('panel-heading', $stdout);
        $this->assertContains('Sales currency: EUR', $stdout);
    }

    public function testExport3ChildWritesStatusSharesAndExits()
    {
        $child = dirname(__DIR__) . '/tests/Support/export-child.php';
        list($exit, $stdout, $stderr) = $this->runPhpChild(array($child, 'states'));
        $this->assertSame(0, $exit);
        $this->assertDiagnosticFreeOutput($stdout, $stderr);
        $this->assertNotContains('panel-heading', $stdout);
        $this->assertContains('Percentage of orders per status.', $stdout);
        $foundPaid = false;
        $foundShipped = false;
        foreach (explode("\n", $stdout) as $line) {
            if (strpos($line, 'Paid;') !== false) {
                $parts = explode(';', trim($line, ';'));
                $this->assertTrue(is_numeric($parts[1]));
                $this->assertSame(0.25, (float) $parts[1]);
                $foundPaid = true;
            }
            if (strpos($line, 'Shipped;') !== false) {
                $parts = explode(';', trim($line, ';'));
                $this->assertTrue(is_numeric($parts[1]));
                $this->assertSame(0.75, (float) $parts[1]);
                $foundShipped = true;
            }
        }
        $this->assertTrue($foundPaid);
        $this->assertTrue($foundShipped);
    }
}
