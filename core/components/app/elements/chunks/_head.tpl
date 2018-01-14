<meta charset="utf-8">
<title>{$_modx->resource['seo.title'] ?: $_modx->resource.longtitle ?: $_modx->resource.pagetitle}</title>
<meta name="keywords" content="{$_modx->resource['seo.keywords']}">
<meta name="description" content="{$_modx->resource['seo.description']}">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
{var $assets = ('assets_url' | config) ~ 'components/app/'}

<meta name="theme-color" content="#333366">

{('<meta name="csrf-token" content="' ~ $.session['csrf-token'] ~ '">') | htmlToHead}
{('<meta name="assets-version" content="' ~ $.assets_version ~ '">') | htmlToHead}
{($assets ~ 'web/main.css?v=' ~ $.assets_version) | cssToHead}
{($assets ~ 'web/main.js?v=' ~ $.assets_version) | jsToBottom : false}