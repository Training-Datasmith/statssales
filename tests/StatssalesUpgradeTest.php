<?php

class StatssalesUpgradeTest extends StatssalesTestCase
{
    public function testUpgrade210UnregistersLegacyHookAndRegistersDisplayHook()
    {
        Module::resetModuleStatics();
        $module = $this->newModule();
        $this->assertTrue(upgrade_module_2_1_0($module));
        $this->assertSame(array('AdminStatsModules'), Module::$unregisteredHooks);
        $this->assertSame(array('displayAdminStatsModules'), Module::$registeredHooks);
    }
}
