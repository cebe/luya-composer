<?php

namespace luya\composer\tests;

use luya\composer\Plugin;
use Composer\Installer\PackageEvent;
use Composer\Script\Event;

class LuyaComposerPluginTest extends TestCase
{
    /**
     * @var Plugin
     */
    protected $plugin;
    
    protected function setUp()
    {
        parent::setUp();
        
        $this->plugin = new Plugin();
        $this->plugin->linkPath = __DIR__ . '/data/luya';
    }
    
    protected function tearDown()
    {
        $this->plugin = null;
        parent::tearDown();
    }
    
    public function testSubscribeEvents()
    {
        $this->plugin->activate($this->composer, $this->io);
        
        $this->assertCount(4, $this->plugin->getSubscribedEvents());
    }
    
    public function testPostUpdateScript()
    {
        $this->invokeSetProperty($this->plugin, '_packageInstalls', [
            'luyadev/luya-module-admin',
            'luyadev/luya-core',
            'luyadev/luya-foo',
        ]);
    
        $scriptEvent = new Event('post-update', $this->composer, $this->io);
    
        $this->plugin->postUpdateScript($scriptEvent);
    
        $luyaLinkTarget = @readlink(__DIR__ . '/data/luya');
        $this->assertNotFalse($luyaLinkTarget, 'Luya file link missing.');
        $this->assertStringStartsNotWith('/', $luyaLinkTarget, 'Link target should not be a absolute path.');
    }
    
}
