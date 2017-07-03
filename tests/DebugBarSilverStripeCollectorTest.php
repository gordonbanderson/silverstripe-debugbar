<?php

class DebugBarSilverStripeCollectorTest extends SapphireTest
{
    /**
     * @var DebugBarSilverStripeCollector
     */
    protected $collector;

    protected $usesDatabase = true;

    public function setUp()
    {
        parent::setUp();
        DebugBar::initDebugBar();
        $this->collector = DebugBar::getDebugBar()->getCollector('silverstripe');
    }

    public function testCollectorExists()
    {
        $this->assertInstanceOf('DebugBarSilverStripeCollector', $this->collector);
    }

    public function testCollect()
    {
        $data = $this->collector->collect();

        $this->assertArrayHasKey('debug', $data);
        $this->assertArrayHasKey('locale', $data);
        $this->assertArrayHasKey('parameters', $data);
        $this->assertContains('Framework', $data['version']);
        $this->assertSame('SiteConfig', $data['config']['ClassName']);
        $this->assertSame('User, ADMIN', $data['user']);
        $this->assertCount(0, $data['requirements']);

        Member::currentUser()->logOut();
        $data = $this->collector->collect();
        $this->assertSame('Not logged in', $data['user']);
    }

    public function testShowRequirements()
    {
        Requirements::css(DEBUGBAR_DIR . '/assets/debugbar.css');
        $data = $this->collector->collect();
        $this->assertContains('assets/debugbar.css', $data['requirements'][0]);
    }

    public function testShowRequestParameters()
    {
        $controller = new Controller;
        $controller->init();
        $controller->setRequest(
            new SS_HTTPRequest(
                'GET',
                '/',
                array('getvar' => 'value', 'foo' => 'bar'),
                array('postvar' => 'value', 'bar' => 'baz')
            )
        );
        $controller->getRequest()->setRouteParams(array('something' => 'here'));

        $this->collector->setController($controller);
        $this->assertSame($controller, $this->collector->getController());

        $result = DebugBarSilverStripeCollector::getRequestParameters();
        $this->assertSame('value', $result['GET - getvar']);
        $this->assertSame('baz', $result['POST - bar']);
        $this->assertSame('here', $result['ROUTE - something']);
    }

    public function testGetSessionData()
    {
        Session::set('DebugBarTesting', 'test value');
        $result = DebugBarSilverStripeCollector::getSessionData();
        $this->assertSame('test value', $result['DebugBarTesting']);
    }

    public function testGetConfigData()
    {
        $result = DebugBarSilverStripeCollector::getConfigData();
        $this->assertSame('SiteConfig', $result['ClassName']);
        $this->assertArrayHasKey('Title', $result);
        $this->assertArrayHasKey('ID', $result);
        $this->assertArrayHasKey('Created', $result);
    }

    public function testGetWidgets()
    {
        $expected = array(
            'user' => array(
                'icon' => 'user',
                'tooltip' => 'Current member',
                'map' => 'silverstripe.user',
                'default' => '',
            ),
            'version' => array(
                'icon' => 'bullseye',
                'tooltip' => 'Version',
                'map' => 'silverstripe.version',
                'default' => '',
            ),
            'locale' => array(
                'icon' => 'flag',
                'tooltip' => 'Current locale',
                'map' => 'silverstripe.locale',
                'default' => '',
            ),
            'session' => array(
                'icon' => 'archive',
                'widget' => 'PhpDebugBar.Widgets.VariableListWidget',
                'map' => 'silverstripe.session',
                'default' => '{}',
            ),
            'cookies' => array(
                'icon' => 'asterisk',
                'widget' => 'PhpDebugBar.Widgets.VariableListWidget',
                'map' => 'silverstripe.cookies',
                'default' => '{}',
            ),
            'parameters' => array(
                'icon' => 'arrow-right',
                'widget' => 'PhpDebugBar.Widgets.VariableListWidget',
                'map' => 'silverstripe.parameters',
                'default' => '{}',
            ),
            'config' => array(
                'icon' => 'gear',
                'widget' => 'PhpDebugBar.Widgets.VariableListWidget',
                'map' => 'silverstripe.config',
                'default' => '{}',
            ),
            'requirements' => array(
                'icon' => 'file-o ',
                'widget' => 'PhpDebugBar.Widgets.ListWidget',
                'map' => 'silverstripe.requirements',
                'default' => '{}',
            )
        );
        $this->assertSame($expected, $this->collector->getWidgets());
    }

    public function testGetAssets()
    {
        $expected = array(
            'base_path' => '/debugbar/javascript',
            'base_url' => 'debugbar/javascript',
            'css' => [],
            'js' => 'widgets.js',
        );
        $this->assertSame($expected, $this->collector->getAssets());
    }
}
