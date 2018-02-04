<?php

return [
    'seo.title' => array(
        'type'      => 'textfield',
        'caption'   => 'Заголовок',
        'rank' => 10,
        '_category' => 'SEO'
    ),
    'seo.keywords' => array(
        'type'      => 'textfield',
        'caption'   => 'Ключевые слова',
        'rank' => 20,
        '_category' => 'SEO'
    ),
    'seo.description' => array(
        'type'      => 'textarea',
        'caption'   => 'Описание',
        'rank' => 30,
        '_category' => 'SEO'
    )
];
