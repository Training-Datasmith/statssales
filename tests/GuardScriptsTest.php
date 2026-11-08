<?php

class GuardScriptsTest extends StatssalesTestCase
{
    public function testStatssalesExitsWhenPsVersionIsMissing()
    {
        $root = dirname(__DIR__);
        $sentinel = sys_get_temp_dir() . '/guard-' . uniqid('', true);
        $child = $root . '/tests/Support/guard-child.php';
        list($exit, $stdout, $stderr) = $this->runPhpChild(array(
            $child,
        ), array(
            'GUARD_FILE' => $root . '/statssales.php',
            'GUARD_SENTINEL' => $sentinel,
        ));
        $flag = is_file($sentinel) ? file_get_contents($sentinel) : '';
        if (is_file($sentinel)) {
            unlink($sentinel);
        }
        $this->assertSame(0, $exit);
        $this->assertSame('exited', $flag);
        $this->assertSame('', $stdout);
        $this->assertSame('', $stderr);
    }

    /**
     * @dataProvider directoryGuards
     */
    public function testDirectoryIndexExits($relativePath)
    {
        $root = dirname(__DIR__);
        $sentinel = sys_get_temp_dir() . '/guard-' . uniqid('', true);
        $child = $root . '/tests/Support/guard-child.php';
        list($exit, $stdout, $stderr) = $this->runPhpChild(array(
            $child,
        ), array(
            'GUARD_FILE' => $root . '/' . $relativePath,
            'GUARD_SENTINEL' => $sentinel,
        ));
        $flag = is_file($sentinel) ? file_get_contents($sentinel) : '';
        if (is_file($sentinel)) {
            unlink($sentinel);
        }
        $this->assertSame(0, $exit);
        $this->assertSame('exited', $flag);
        $this->assertSame('', $stdout);
        $this->assertSame('', $stderr);
    }

    public function directoryGuards()
    {
        return array(
            array('index.php'),
            array('tests/index.php'),
            array('translations/index.php'),
            array('upgrade/index.php'),
        );
    }
}
