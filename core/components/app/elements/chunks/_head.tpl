<meta charset="utf-8">
<title>{$.resource['seo.title'] ?: $.resource.longtitle ?: $.resource.pagetitle}</title>
<meta name="keywords" content="{$.resource['seo.keywords']}">
<meta name="description" content="{$.resource['seo.description']}">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">

<meta name="theme-color" content="#333366">

{('<meta name="csrf-token" content="' ~ $.session['csrf-token'] ~ '">') | htmlToHead}
{('<meta name="assets-version" content="' ~ $.assets_version ~ '">') | htmlToHead}
{($.assets_url ~ 'web/main.css?v=' ~ $.assets_version) | cssToHead}
{($.assets_url ~ 'web/main.js?v=' ~ $.assets_version) | jsToBottom : false}
{('<script>window.assets_url = "' ~ $.assets_url ~ '"</script>') | jsToHead}
