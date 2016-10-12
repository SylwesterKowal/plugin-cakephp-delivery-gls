<?php
namespace Gls\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\TestSuite\TestCase;
use Gls\Controller\Component\GlsComponent;

/**
 * Gls\Controller\Component\GlsComponent Test Case
 */
class GlsComponentTest extends TestCase
{

    /**
     * Test subject
     *
     * @var \Gls\Controller\Component\GlsComponent
     */
    public $Gls;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $registry = new ComponentRegistry();
        $this->Gls = new GlsComponent($registry);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Gls);

        parent::tearDown();
    }

    /**
     * Test initial setup
     *
     * @return void
     */
    public function testInitialization()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
