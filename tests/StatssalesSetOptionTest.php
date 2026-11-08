<?php

class StatssalesSetOptionTest extends StatssalesTestCase
{
    public function testOption1SetsThreeSeriesTitles()
    {
        $probe = $this->newProbe();
        $probe->setOption('1-0', 2);
        $titles = $probe->probeTitles();
        $this->assertSame('Orders placed', $titles['main'][0]);
        $this->assertSame('Products bought', $titles['main'][1]);
        $this->assertSame('Products:', $titles['main'][2]);
    }

    public function testOption2SetsSalesTitleWithCurrencyIso()
    {
        $probe = $this->newProbe();
        $probe->setOption('2-4', 1);
        $titles = $probe->probeTitles();
        $this->assertSame('Sales currency: EUR', $titles['main']);
        $last = end(ModuleTranslatorSpy::$log);
        $this->assertSame('Sales currency: %s', $last['id']);
        $this->assertSame(array('EUR'), $last['parameters']);
    }

    public function testOption3SetsStatusTitleAndUnknownOptionLeavesTitleNull()
    {
        $probe = $this->newProbe();
        $probe->setOption('3-5', 1);
        $this->assertSame('Percentage of orders per status.', $probe->probeTitles()['main']);
        $probe2 = $this->newProbe();
        $probe2->setOption('4-1', 1);
        $this->assertNull($probe2->probeTitles()['main']);
    }
}
