<?php

require_once dirname(__DIR__) . '/Support/StatssalesProbe.php';
require_once dirname(__DIR__) . '/Support/StatssalesFixtures.php';

class StatssalesTestCase extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->resetHarness();
        $_SERVER['REQUEST_URI'] = '/admin/stats';
        Db::truncateAll();
        $this->setEmployeeWindow('2020-06-01', '2020-06-20');
        Country::resetCountries();
        Country::$byLang[7] = array(
            array('id_country' => 8, 'name' => 'France'),
            array('id_country' => 9, 'name' => 'Germany'),
        );
        Country::$byLang[1] = array(
            array('id_country' => 8, 'name' => 'Frankreich'),
        );
    }

    protected function tearDown()
    {
        $this->resetHarness();
    }

    protected function resetHarness()
    {
        Tools::resetValues();
        Configuration::resetStore();
        Country::resetCountries();
        Currency::resetMap();
        Shop::resetShop();
        Context::resetContext();
        LocaleSpy::reset();
        ModuleTranslatorSpy::reset();
        Module::resetModuleStatics();
        $this->setEmployeeWindow('2020-06-01', '2020-06-20');
    }

    protected function setEmployeeWindow($from, $to)
    {
        $ctx = Context::getContext();
        $ctx->employee->stats_date_from = $from;
        $ctx->employee->stats_date_to = $to;
    }

    protected function newModule()
    {
        return new statssales();
    }

    protected function newProbe()
    {
        return new StatssalesProbe();
    }

    protected function invokeGetTotals(statssales $module)
    {
        $ref = new ReflectionClass($module);
        $method = $ref->getMethod('getTotals');
        $method->setAccessible(true);

        return $method->invoke($module);
    }

    protected function assertMoney($expectedCents, $actual)
    {
        $this->assertTrue(is_numeric($actual), 'Expected numeric money, got: ' . var_export($actual, true));
        $this->assertSame((int) $expectedCents, (int) round((float) $actual * 100));
    }

    protected function assertDiagnosticFreeOutput($stdout, $stderr)
    {
        $combined = $stdout . "\n" . $stderr;
        $patterns = array(
            'Notice:',
            'Warning:',
            'Deprecated:',
            'Strict Standards:',
            'Parse error:',
            'Fatal error:',
        );
        foreach ($patterns as $pat) {
            $this->assertNotContains($pat, $combined, 'Unexpected PHP diagnostic in child output');
        }
    }

    protected function runPhpChild(array $args, $env = array())
    {
        $php = PHP_BINARY;
        $cmd = escapeshellarg($php)
            . ' -d error_reporting=-1 -d display_errors=1 -d display_startup_errors=1 -d date.timezone=UTC';
        foreach ($args as $arg) {
            $cmd .= ' ' . escapeshellarg($arg);
        }
        $descriptorSpec = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w'),
        );
        $fullEnv = array_merge($_ENV, $env);
        $proc = proc_open($cmd, $descriptorSpec, $pipes, dirname(dirname(__DIR__)), $fullEnv);
        $this->assertTrue(is_resource($proc));
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exit = proc_close($proc);

        return array($exit, $stdout, $stderr);
    }
}
