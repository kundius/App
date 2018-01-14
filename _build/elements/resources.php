<?php

return [
    'web' => [
        'index' => [
            'pagetitle' => 'Home',
            'hidemenu' => false,
            'properties' => [
                'template' => 'BaseTemplate'
            ]
        ],
        'service' => [
            'pagetitle' => 'Service',
            'hidemenu' => true,
            'published' => false,
            'resources' => [
                '404' => [
                    'pagetitle' => '404',
                    'hidemenu' => true,
                    'uri' => '404',
                    'uri_override' => true,
                    'properties' => [
                        'template' => 'BaseTemplate'
                    ]
                ],
                'sitemap.xml' => [
                    'pagetitle' => 'Sitemap',
                    'template' => 0,
                    'hidemenu' => true,
                    'uri' => 'sitemap.xml',
                    'uri_override' => true,
                ],
            ],
            'properties' => [
                'template' => 'BaseTemplate'
            ]
        ],
    ],
];