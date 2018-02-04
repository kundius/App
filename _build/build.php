<?php

class AppPackage
{
    /** @var modX $modx */
    public $modx;
    /** @var array $config */
    public $config = [];
    /** @var modPackageBuilder $builder */
    public $builder;

    protected $_elements = [];
    protected $_resources = [];

    const name = 'App';
    const name_lower = 'app';
    const version = '1.0.2';
    const release = 'pl';


    /**
     * AppPackage constructor.
     *
     * @param $core_path
     * @param array $config
     */
    public function __construct($core_path, array $config = [])
    {
        /** @noinspection PhpIncludeInspection */
        require $core_path . 'model/modx/modx.class.php';
        /** @var modX $modx */
        $this->modx = new modX();
        $this->modx->initialize('mgr');
        $this->modx->getService('error', 'error.modError');

        $root = dirname(dirname(__FILE__)) . '/';
        $assets = $root . 'assets/components/' . $this::name_lower . '/';
        $core = $root . 'core/components/' . $this::name_lower . '/';

        $this->config = array_merge([
            'log_level' => modX::LOG_LEVEL_INFO,
            'log_target' => 'ECHO',

            'root' => $root,
            'build' => $root . '_build/',
            'elements' => $root . '_build/elements/',
            'resolvers' => $root . '_build/resolvers/',

            'assets' => $assets,
            'core' => $core,
        ], $config);
        $this->modx->setLogLevel($this->config['log_level']);
        $this->modx->setLogTarget($this->config['log_target']);
        if (!XPDO_CLI_MODE) {
            echo '<pre>';
        }

        $this->initialize();
    }


    /**
     * Initialize package builder
     */
    protected function initialize()
    {
        $this->builder = $this->modx->getService('transport.modPackageBuilder');
        $this->builder->createPackage($this::name_lower, $this::version, $this::release);
        $this->builder->registerNamespace($this::name_lower, false, true, '{core_path}components/' . $this::name_lower . '/');
        $this->modx->log(modX::LOG_LEVEL_INFO, 'Created Transport Package and Namespace.');
    }


    /**
     * Update the model
     */
    protected function model()
    {
        if (empty($this->config['core'] . 'model/schema/' . $this::name_lower . '.mysql.schema.xml')) {
            return;
        }
        /** @var xPDOCacheManager $cache */
        if ($cache = $this->modx->getCacheManager()) {
            $cache->deleteTree(
                $this->config['core'] . 'model/' . $this::name_lower . '/mysql',
                ['deleteTop' => true, 'skipDirs' => false, 'extensions' => []]
            );
        }

        /** @var xPDOManager $manager */
        $manager = $this->modx->getManager();
        /** @var xPDOGenerator $generator */
        $generator = $manager->getGenerator();
        $generator->parseSchema(
            $this->config['core'] . 'model/schema/' . $this::name_lower . '.mysql.schema.xml',
            $this->config['core'] . 'model/'
        );
        $this->modx->log(modX::LOG_LEVEL_INFO, 'Model updated');
    }


    /**
     * Install nodejs and update assets
     */
    protected function assets()
    {
        if (!file_exists($this->config['build'] . 'node_modules')) {
            putenv('PATH=' . trim(shell_exec('echo $PATH')) . ':' . dirname(MODX_BASE_PATH) . '/');
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Trying to install or update nodejs dependencies');
            $output = [
                shell_exec('cd ' . $this->config['build'] . ' && npm config set scripts-prepend-node-path true && npm install'),
            ];
            $this->modx->log(xPDO::LOG_LEVEL_INFO, implode("\n", array_map('trim', $output)));
        }
        $output = shell_exec('cd ' . $this->config['build'] . ' && npm run build 2>&1');
        $this->modx->log(xPDO::LOG_LEVEL_INFO, 'Compile scripts and styles ' . trim($output));
    }


    /**
     * Add settings
     */
    protected function settings()
    {
        /** @noinspection PhpIncludeInspection */
        $settings = include($this->config['elements'] . 'settings.php');
        if (!is_array($settings)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Could not package in settings');

            return;
        }
        $attributes = [
            xPDOTransport::UNIQUE_KEY => 'key',
            xPDOTransport::PRESERVE_KEYS => true,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::RELATED_OBJECTS => false,
        ];
        $output = [];
        foreach ($settings as $name => $data) {
            /** @var modSystemSetting $setting */
            $setting = $this->modx->newObject('modSystemSetting');
            $setting->fromArray(array_merge([
                'key' => 'app_' . $name,
                'namespace' => $this::name_lower,
            ], $data), '', true, true);
            $output[] = [
                'object' => $setting,
                'attributes' => $attributes
            ];
        }
        return $output;
    }


    /**
     * @param $filename
     *
     * @return string
     */
    protected function _getContent($filename)
    {
        $file = trim(file_get_contents($filename));
        preg_match('#\<\?php(.*)#is', $file, $data);

        return rtrim(rtrim(trim($data[1]), '?>'));
    }


    /**
     * @param array $data
     * @param string $uri
     * @param int $parent
     *
     * @return array
     */
    protected function _addResource(array $data, $uri, $parent = false)
    {
        $file = $data['context_key'] . '/' . $uri;

        /** @var modResource $resource */
        $resource = $this->modx->newObject('modResource');
        $resource->fromArray(array_merge([
            'parent' => 0,
            'published' => true,
            'deleted' => false,
            'hidemenu' => false,
            'createdon' => time(),
            'template' => 0,
            'isfolder' => !empty($data['isfolder']) || !empty($data['resources']),
            'uri' => $uri,
            'uri_override' => false,
            'searchable' => true,
            'richtext' => false,
            'content' => file_exists($this->config['core'] . "elements/resources/{$file}.tpl")
                ? "{include 'file:resources/{$file}.tpl'}"
                : '',
        ], $data), '', true, true);

        if (!empty($data['groups'])) {
            foreach ($data['groups'] as $group) {
                $resource->joinGroup($group);
            }
        }

        if($parent) {
            $resource->addOne($parent);
        }

        $this->_resources[] = $resource;

        if(empty($data['resources'])) {
            return [$resource];
        } else {
            $menuindex = 0;
            $resources = [];
            foreach ($data['resources'] as $alias => $item) {
                $item['alias'] = $alias;
                $item['context_key'] = $data['context_key'];
                $item['menuindex'] = $menuindex++;
                $resources = array_merge(
                    $resources,
                    $this->_addResource($item, $uri . '/' . $alias, $resource)
                );
            }
            return $resources;
        }
    }


    /**
     * Add resources
     */
    protected function resources()
    {
        /** @noinspection PhpIncludeInspection */
        $resources = include($this->config['elements'] . 'resources.php');
        if (!is_array($resources)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Could not package in Resources');

            return;
        }
        $attributes = [
            xPDOTransport::UNIQUE_KEY => 'uri',
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::RELATED_OBJECTS => true,
            xPDOTransport::RELATED_OBJECT_ATTRIBUTES => [
                'Parent' => [
                    xPDOTransport::PRESERVE_KEYS => false,
                    xPDOTransport::UPDATE_OBJECT => true,
                    xPDOTransport::UNIQUE_KEY => 'uri'
                ],
                'Template' => [
                    xPDOTransport::PRESERVE_KEYS => false,
                    xPDOTransport::UPDATE_OBJECT => true,
                    xPDOTransport::UNIQUE_KEY => 'templatename'
                ]
            ],
        ];

        $objects = [];
        foreach ($resources as $context => $items) {
            $menuindex = 0;
            foreach ($items as $alias => $item) {
                $item['alias'] = $alias;
                $item['context_key'] = $context;
                $item['menuindex'] = $menuindex++;
                $objects = array_merge(
                    $objects,
                    $this->_addResource($item, $alias)
                );
            }
        }
        
        $output = [];
        /** @var modResource $resource */
        foreach ($objects as $resource) {
            $tmp = [
                'object' => $resource,
                'attributes' => $attributes
            ];
            $output[] = $tmp;
        }
        return $output;
    }


    /**
     * Add plugins
     */
    protected function plugins()
    {
        /** @noinspection PhpIncludeInspection */
        $plugins = include($this->config['elements'] . 'plugins.php');
        if (!is_array($plugins)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Could not package in Plugins');

            return;
        }

        $attributes = [
            xPDOTransport::UNIQUE_KEY => 'name',
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::RELATED_OBJECTS => true,
            xPDOTransport::RELATED_OBJECT_ATTRIBUTES => [
                'PluginEvents' => [
                    xPDOTransport::PRESERVE_KEYS => true,
                    xPDOTransport::UPDATE_OBJECT => true,
                    xPDOTransport::UNIQUE_KEY => ['pluginid', 'event'],
                ],
            ],
        ];

        $output = [];
        foreach ($plugins as $name => $data) {
            /** @var modPlugin $plugin */
            $plugin = $this->modx->newObject('modPlugin');
            $plugin->fromArray([
                'name' => $name,
                'category' => 0,
                'description' => @$data['description'],
                'plugincode' => $this->_getContent($this->config['core'] . 'elements/plugins/' . $data['file'] . '.php'),
                'static' => false,
                'source' => 1,
                'static_file' => 'core/components/' . $this::name_lower . '/elements/plugins/' . $data['file'] . '.php',
            ], '', true, true);

            $events = [];
            if (!empty($data['events'])) {
                foreach ($data['events'] as $event_name => $event_data) {
                    /** @var modPluginEvent $event */
                    $event = $this->modx->newObject('modPluginEvent');
                    $event->fromArray(array_merge([
                        'event' => $event_name,
                        'priority' => 0,
                        'propertyset' => 0,
                    ], $event_data), '', true, true);
                    $events[] = $event;
                }
            }
            if (!empty($events)) {
                $plugin->addMany($events);
            }
            $output[] = [
                'object' => $plugin,
                'attributes' => $attributes
            ];
        }
        return $output;
    }


    /**
     * Add templates
     */
    protected function templates()
    {
        /** @noinspection PhpIncludeInspection */
        $templates = include($this->config['elements'] . 'templates.php');
        if (!is_array($templates)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Could not package in Templates');

            return;
        }

        $attributes = [
            xPDOTransport::UNIQUE_KEY => 'templatename',
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true
        ];

        $output = [];
        foreach ($templates as $name => $data) {
            /** @var modTemplate $template */
            $template = $this->modx->newObject('modTemplate');
            $template->fromArray(array_merge([
                'templatename' => $name,
                'content' => file_exists($this->config['core'] . "elements/templates/{$data['file']}.tpl")
                    ? "{include 'file:templates/{$data['file']}.tpl'}"
                    : ''
            ],$data), '', true, true);
            $output[$name] = [
                'object' => $template,
                'attributes' => $attributes
            ];
        }
        return $output;
    }


    /**
     * Add template modCategory
     */
    protected function categories()
    {
        /** @noinspection PhpIncludeInspection */
        $categories = include($this->config['elements'] . 'categories.php');
        if (!is_array($categories)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Could not package in modCategory');

            return;
        }
        $attributes = [
            xPDOTransport::UNIQUE_KEY => 'category',
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
        ];
        $output = [];
        foreach ($categories as $name => $data) {
            /** @var modCategory $category */
            $category = $this->modx->newObject('modCategory');
            $category->fromArray(array_merge([
                'category' => $name
            ], $data), '', true, true);
            $output[$name] = [
                'object' => $category,
                'attributes' => $attributes
            ];
        }
        return $output;
    }


    /**
     * Add template Vars
     */
    protected function tmplvars()
    {
        /** @noinspection PhpIncludeInspection */
        $tmplvars = include($this->config['elements'] . 'tmplvars.php');
        if (!is_array($tmplvars)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Could not package in TemplateVars');

            return;
        }
        $attributes = [
            xPDOTransport::UNIQUE_KEY => 'name',
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::RELATED_OBJECTS => true,
            xPDOTransport::RELATED_OBJECT_ATTRIBUTES => [
                'Category' => [
                    xPDOTransport::UNIQUE_KEY => 'category',
                    xPDOTransport::PRESERVE_KEYS => false,
                    xPDOTransport::UPDATE_OBJECT => true,
                ]
            ],
        ];
        $output = [];
        foreach ($tmplvars as $name => $data) {
            /** @var modTemplateVar $tv */
            $tv = $this->modx->newObject('modTemplateVar');
            $tv->fromArray(array_merge([
                'name' => $name
            ], $data), '', true, true);
            
            if (
                $tv->_category &&
                $category = $this->_elements['categories'][$tv->_category]['object']
            ) {
                $tv->addOne($category);
            }

            $output[$name] = [
                'object' => $tv,
                'attributes' => $attributes
            ];
        }
        return $output;
    }


    /**
     *  Install package
     */
    protected function install()
    {
        $signature = $this->builder->getSignature();
        $sig = explode('-', $signature);
        $versionSignature = explode('.', $sig[1]);

        /** @var modTransportPackage $package */
        if (!$package = $this->modx->getObject('transport.modTransportPackage', ['signature' => $signature])) {
            $package = $this->modx->newObject('transport.modTransportPackage');
            $package->set('signature', $signature);
            $package->fromArray([
                'created' => date('Y-m-d h:i:s'),
                'updated' => null,
                'state' => 1,
                'workspace' => 1,
                'provider' => 0,
                'source' => $signature . '.transport.zip',
                'package_name' => $this::name,
                'version_major' => $versionSignature[0],
                'version_minor' => !empty($versionSignature[1]) ? $versionSignature[1] : 0,
                'version_patch' => !empty($versionSignature[2]) ? $versionSignature[2] : 0,
            ]);
            if (!empty($sig[2])) {
                $r = preg_split('#([0-9]+)#', $sig[2], -1, PREG_SPLIT_DELIM_CAPTURE);
                if (is_array($r) && !empty($r)) {
                    $package->set('release', $r[0]);
                    $package->set('release_index', (isset($r[1]) ? $r[1] : '0'));
                } else {
                    $package->set('release', $sig[2]);
                }
            }
            $package->save();
        }
        if ($package->install()) {
            $this->modx->runProcessor('system/clearcache');
        }
    }


    /**
     * @param bool $install
     *
     * @return modPackageBuilder
     */
    public function process($install = true)
    {
        ob_start();
        $this->model();
        $this->assets();

        // Add elements
        $elements = scandir($this->config['elements']);
        foreach ($elements as $element) {
            if (in_array($element[0], ['_', '.'])) {
                continue;
            }
            $name = preg_replace('#\.php$#', '', $element);
            if (method_exists($this, $name)) {
                $this->_elements[$name] = $this->{$name}();
            }
        }

        // set resource templates
        foreach ($this->_resources as $resource) {
            if(
                $resource->_template && 
                $object = $this->_elements['templates'][$resource->_template]['object']
            ) {
                $resource->addOne($object);
            }
        }

        // link tmplvars to templates
        $tmplvartemplates = [];
        foreach ($this->_elements['templates'] as $name => $template) {
            if(empty($template['object']->_tmplvars)) continue;

            $attributes = [
                xPDOTransport::UNIQUE_KEY => array('tmplvarid', 'templateid'),
                xPDOTransport::PRESERVE_KEYS => false,
                xPDOTransport::UPDATE_OBJECT => true,
                xPDOTransport::RELATED_OBJECTS => true,
                xPDOTransport::RELATED_OBJECT_ATTRIBUTES => [
                    'TemplateVar' => [
                        xPDOTransport::UNIQUE_KEY => 'name',
                        xPDOTransport::PRESERVE_KEYS => false,
                        xPDOTransport::UPDATE_OBJECT => false,
                        xPDOTransport::RELATED_OBJECTS => true,
                        xPDOTransport::RELATED_OBJECT_ATTRIBUTES => [
                            'Category' => [
                                xPDOTransport::UNIQUE_KEY => 'category',
                                xPDOTransport::PRESERVE_KEYS => false,
                                xPDOTransport::UPDATE_OBJECT => false,
                            ]
                        ],
                    ],
                    'Template' => [
                        xPDOTransport::UNIQUE_KEY => 'templatename',
                        xPDOTransport::PRESERVE_KEYS => false,
                        xPDOTransport::UPDATE_OBJECT => false,
                        xPDOTransport::RELATED_OBJECTS => false,
                    ],
                ],
            ];
            
            foreach ($template['object']->_tmplvars as $tmplvar) {
                if(empty($this->_elements['tmplvars'][$tmplvar]['object'])) continue;

                $tmplvartemplate = $this->modx->newObject('modTemplateVarTemplate');
                $tmplvartemplate->addOne($template['object']);
                $tmplvartemplate->addOne($this->_elements['tmplvars'][$tmplvar]['object']);
                $tmplvartemplates[] = [
                    'object' => $tmplvartemplate,
                    'attributes' => $attributes
                ];
            }
        }
        $this->_elements['tmplvartemplates'] = $tmplvartemplates;

        // Add vehicles
        foreach ($this->_elements as $name => $rows) {
            foreach ($rows as $row) {
                $vehicle = $this->builder->createVehicle($row['object'], $row['attributes']);
                $this->builder->putVehicle($vehicle);
            }
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Packaged in ' . count($rows) . ' ' . $name);
        }

        // Create main vehicle
        $vehicle = $this->builder->createVehicle([
            'source' => $this->config['core'],
            'target' => "return MODX_CORE_PATH . 'components/';",
        ], [
            'vehicle_class' => 'xPDOFileVehicle',
        ]);
        $vehicle->resolve('file', [
            'source' => $this->config['assets'],
            'target' => "return MODX_ASSETS_PATH . 'components/';",
        ]);

        // Add resolvers into vehicle
        $resolvers = scandir($this->config['resolvers']);
        foreach ($resolvers as $resolver) {
            if (in_array($resolver[0], ['_', '.'])) {
                continue;
            }
            if ($vehicle->resolve('php', ['source' => $this->config['resolvers'] . $resolver])) {
                $this->modx->log(modX::LOG_LEVEL_INFO, 'Added resolver ' . $name = preg_replace('#\.php$#', '', $resolver));
            }
        }
        $this->builder->putVehicle($vehicle);

        $this->builder->setPackageAttributes([
            'changelog' => file_get_contents($this->config['core'] . 'docs/changelog.txt'),
            'license' => file_get_contents($this->config['core'] . 'docs/license.txt'),
            'readme' => file_get_contents($this->config['core'] . 'docs/readme.txt'),
        ]);
        $this->modx->log(modX::LOG_LEVEL_INFO, 'Added package attributes and setup options.');

        $this->modx->log(modX::LOG_LEVEL_INFO, 'Packing up transport package zip...');
        $this->builder->pack();

        if ($install) {
            $this->install();
        }

        return $this->builder;
    }

}

$core = dirname(dirname(dirname(dirname(__FILE__)))) . '/config.core.php';
if (!file_exists($core)) {
    exit('Could not load config core!');
}
/** @noinspection PhpIncludeInspection */
require $core;
$install = new AppPackage(MODX_CORE_PATH);
$builder = $install->process(true);
$signature = $builder->getSignature();

$install->modx->log(modX::LOG_LEVEL_INFO, 'Download archive: ' . $install->modx->getOption('site_url') . 'core/packages/' . $signature . '.transport.zip');

if (!empty($_GET['download'])) {
    echo '<script>document.location.href = "/core/packages/' . $signature . '.transport.zip' . '";</script>';
}
