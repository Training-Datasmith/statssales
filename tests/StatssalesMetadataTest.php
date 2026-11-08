<?php

class StatssalesMetadataTest extends StatssalesTestCase
{
    public function testConstructorPublishesModuleIdentity()
    {
        $module = $this->newModule();
        $this->assertSame('statssales', $module->name);
        $this->assertSame('administration', $module->tab);
        $this->assertSame('2.1.1', $module->version);
        $this->assertSame('PrestaShop', $module->author);
        $this->assertSame(0, $module->need_instance);
        $this->assertSame(array('min' => '1.7.6.0', 'max' => '1.7.6.0'), $module->ps_versions_compliancy);
    }

    public function testConstructorTranslatesNameAndDescription()
    {
        $module = $this->newModule();
        $this->assertSame('Sales and orders', $module->displayName);
        $this->assertSame(
            'Enrich your stats, add graphics presenting the evolution of your sales and orders.',
            $module->description
        );
    }

    public function testVersionMatchesConfigXmlLiteral()
    {
        $module = $this->newModule();
        $xml = file_get_contents(dirname(__DIR__) . '/config.xml');
        $this->assertSame(1, preg_match('/<version><!\[CDATA\[([^]]+)\]\]><\/version>/', $xml, $m));
        $this->assertSame('2.1.1', $m[1]);
        $this->assertSame($m[1], $module->version);
    }
}
