<?php

return [
    'container_suffix' => [
        'key' => 'container_suffix',
        'xtype' => 'textfield',
        'value' => '',
        'area' => 'furls',
        'namespace' => 'core',
    ],
    'friendly_urls_strict' => [
        'key' => 'friendly_urls_strict',
        'xtype' => 'combo-boolean',
        'value' => true,
        'area' => 'furls',
        'namespace' => 'core',
    ],
    'use_alias_path' => [
        'key' => 'use_alias_path',
        'xtype' => 'combo-boolean',
        'value' => true,
        'area' => 'furls',
        'namespace' => 'core',
    ],
    'friendly_urls' => [
        'key' => 'friendly_urls',
        'xtype' => 'combo-boolean',
        'value' => true,
        'area' => 'furls',
        'namespace' => 'core',
    ],
    'link_tag_scheme' => [
        'key' => 'link_tag_scheme',
        'xtype' => 'textfield',
        'value' => 'abs',
        'area' => 'site',
        'namespace' => 'core',
    ],
    'pdotools_elements_path' => [
        'key' => 'pdotools_elements_path',
        'xtype' => 'textfield',
        'value' => '{core_path}components/app/elements/',
        'area' => 'pdotools_main',
        'namespace' => 'pdotools',
    ],
    'fenom_parser' => [
        'key' => 'pdotools_fenom_parser',
        'xtype' => 'combo-boolean',
        'value' => true,
        'area' => 'pdotools_main',
        'namespace' => 'pdotools',
    ],
];
