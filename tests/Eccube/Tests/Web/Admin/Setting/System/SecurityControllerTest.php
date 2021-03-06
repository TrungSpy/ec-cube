<?php
/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) 2000-2015 LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */


namespace Eccube\Tests\Web\Admin\Setting\Shop;

use Eccube\Tests\Web\Admin\AbstractAdminWebTestCase;
use org\bovigo\vfs\vfsStream;
use Symfony\Component\Yaml\Yaml;

/**
 * Class SecurityControllerTest
 * @package Eccube\Tests\Web\Admin\Setting\Shop
 */
class SecurityControllerTest extends AbstractAdminWebTestCase
{
    protected $configFile;
    protected $pathFile;

    protected $configFileReal;
    protected $pathFileReal;

    protected $ipTest = '192.168.1.100';

    /**
     * Setup before test
     */
    public function setUp()
    {
        parent::setUp(); // TODO: Change the autogenerated stub

        // virtual directory
        vfsStream::setup('rootDir');
        $config = $this->app['config'];

        $this->configFileReal = $config['root_dir'].'/app/config/eccube/config.yml';
        $this->pathFileReal = $config['root_dir'].'/app/config/eccube/path.yml';
        if (!file_exists($this->configFileReal) || !file_exists($this->pathFileReal)) {
            $this->markTestSkipped('Skip if not have config file');
        }

        $structure = array(
            'app' => array(
                'config' => array(
                    'eccube' => array(
                        'config.yml' => file_get_contents($this->configFileReal),
                        'path.yml' => file_get_contents($this->pathFileReal),
                    ),
                ),
            ),
        );

        $config['root_dir'] = vfsStream::url('rootDir');

        // dump file
        $this->configFile = $config['root_dir'].'/app/config/eccube/config.yml';
        $this->pathFile = $config['root_dir'].'/app/config/eccube/path.yml';

        $this->app['config'] = $config;
        vfsStream::create($structure);
    }

    /**
     * Routing test
     */
    public function testRouting()
    {
        $this->client->request('GET', $this->app->url('admin_setting_system_security'));
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    /**
     * Submit test
     */
    public function testSubmit()
    {
        $formData = $this->createFormData();

        $this->client->request(
            'POST',
            $this->app->url('admin_setting_system_security'),
            array(
                'admin_security' => $formData,
            )
        );

        $this->assertTrue($this->client->getResponse()->isRedirection());
        // Message
        $outPut = $this->app['session']->getFlashBag()->get('eccube.admin.success');
        $this->actual = array_shift($outPut);
        $this->expected = 'admin.system.security.route.dir.complete';
        $this->verify();

        $config = Yaml::parse(file_get_contents($this->configFile));
        $this->assertTrue(in_array($formData['admin_allow_host'], $config['admin_allow_host']));

        $path = Yaml::parse(file_get_contents($this->pathFile));
        $this->expected = $formData['admin_route_dir'];
        $this->actual = $path['admin_route'];
        $this->verify();
    }

    /**
     * Submit when empty
     */
    public function testSubmitEmpty()
    {
        $formData = $this->createFormData();
        $formData['admin_allow_host'] = null;
        $formData['force_ssl'] = null;
        $formData['admin_route_dir'] = $this->app['config']['admin_route'];

        $this->client->request(
            'POST',
            $this->app->url('admin_setting_system_security'),
            array(
                'admin_security' => $formData,
            )
        );

        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $config = Yaml::parse(file_get_contents($this->configFile));
        $this->assertNull($config['admin_allow_host']);
    }

    /**
     * Submit form
     * @return array
     */
    public function createFormData()
    {
        $formData = array(
            '_token' => 'dummy',
            'admin_route_dir' => 'admintest',
            'admin_allow_host' => $this->ipTest,
            'force_ssl' => 1,
        );

        return $formData;
    }
}
