<?php

class StatssalesInstallTest extends StatssalesTestCase
{
    public function testInstallRegistersDisplayAdminStatsModules()
    {
        Module::$parentInstallReturns = true;
        $module = $this->newModule();
        $this->assertTrue($module->install());
        $this->assertSame(array('displayAdminStatsModules'), Module::$registeredHooks);
    }

    public function testInstallDoesNotRegisterHookWhenParentFails()
    {
        Module::$parentInstallReturns = false;
        $module = $this->newModule();
        $this->assertFalse($module->install());
        $this->assertSame(array(), Module::$registeredHooks);
    }
}
