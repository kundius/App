<?php
/** @var xPDOTransport $transport */
/** @var array $options */
/** @var modX $modx */
if ($transport->xpdo) {
    $modx =& $transport->xpdo;

    $dev = MODX_BASE_PATH . 'Extras/App/';
    if (file_exists($dev)) {
        $settings = array(
            'app_core_path' => array(
                'xtype' => 'textfield',
                'value' => '{base_path}Extras/App/core/components/app/',
                'area' => 'default',
                'namespace' => 'app',
            ),
            'app_assets_path' => array(
                'xtype' => 'textfield',
                'value' => '{base_path}Extras/App/assets/components/app/',
                'area' => 'default',
                'namespace' => 'app',
            ),
            'app_assets_url' => array(
                'xtype' => 'textfield',
                'value' => '/Extras/App/assets/components/app/',
                'area' => 'default',
                'namespace' => 'app',
            ),
            'pdotools_elements_path' => array(
                'xtype' => 'textfield',
                'value' => '{base_path}Extras/App/core/components/app/elements/',
                'area' => 'pdotools_main',
                'namespace' => 'pdotools',
            )
        );

        foreach ($settings as $key => $value) {
            if(!$setting = $modx->getObject('modSystemSetting', array('key' => $key))) {
                $setting = $modx->newObject('modSystemSetting');
                $setting->set('key', $key);
                $setting->fromArray(
                    $value
                );
            }
            $setting->set('value', $value['value']);
            $setting->save();
        }

        if($namespace = $modx->getObject('modNamespace', 'app')) {
            $namespace->fromArray(array(
                'path' => '{base_path}Extras/App/core/components/app/',
                'assets_path' => '{base_path}Extras/App/assets/components/app/'
            ));
            $namespace->save();
        }
    }
}

return true;