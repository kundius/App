<?php

return [
    'web' => [
        'index' => [
            'pagetitle' => 'Home',
            'hidemenu' => false,
            '_template' => 'BaseTemplate'
        ],
        'service' => [
            'pagetitle' => 'Service',
            'hidemenu' => true,
            'published' => false,
            '_template' => 'BaseTemplate',
            'resources' => [
                '404' => [
                    'pagetitle' => '404',
                    'hidemenu' => true,
                    'uri' => '404',
                    'uri_override' => true,
                    'template' => 'BaseTemplate'
                ],
                'sitemap.xml' => [
                    'pagetitle' => 'Sitemap',
                    'template' => 0,
                    'hidemenu' => true,
                    'uri' => 'sitemap.xml',
                    'uri_override' => true,
                ],
            ],
        ],
    ],
];
