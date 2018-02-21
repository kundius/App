<?php

class App
{
    /** @var modX $modx */
    public $modx;
    /** @var pdoFetch $pdo */
    public $pdoTools;
    public $config = [];

    const assets_version = '1.00';


    /**
     * @param modX $modx
     * @param array $config
     */
    function __construct(modX &$modx, array $config = [])
    {
        $this->modx = &$modx;
        $corePath = $this->modx->getOption('app_core_path', $config, $this->modx->getOption('core_path') . 'components/app/');
        $assetsUrl = $this->modx->getOption('app_assets_url', $config, $this->modx->getOption('assets_url') . 'components/app/');

        $this->config = array_merge([
            'corePath' => $corePath,
            'modelPath' => $corePath . 'model/',
            'processorsPath' => $corePath . 'processors/',
            'assetsUrl' => $assetsUrl,
            'connectorUrl' => $assetsUrl . 'connector.php',
        ], $config);

        $this->modx->addPackage('app', $this->config['modelPath']);
        $this->modx->lexicon->load('app:default');
    }


    /**
     * Initialize App
     */
    public function initialize()
    {
        $this->pdoTools = $this->modx->getService('pdoFetch');
        if (!isset($_SESSION['csrf-token'])) {
            $_SESSION['csrf-token'] = bin2hex(openssl_random_pseudo_bytes(16));
        }

        //$this->modx->addPackage('app', $this->config['modelPath']);
        /** @noinspection PhpIncludeInspection */
        //require_once $this->config['corePath'] . 'vendor/autoload.php';
    }


    /**
     * @param $action
     * @param array $data
     *
     * @return array|bool|mixed
     */
    public function runProcessor($action, array $data = [])
    {
        $action = 'web/' . trim($action, '/');
        /** @var modProcessorResponse $response */
        $response = $this->modx->runProcessor($action, $data, ['processors_path' => $this->config['processorsPath']]);
        if ($response) {
            $data = $response->getResponse();
            if (is_string($data)) {
                $data = json_decode($data, true);
            }

            return $data;
        }

        return false;
    }


    /**
     * @param modSystemEvent $event
     * @param array $scriptProperties
     */
    public function handleEvent(modSystemEvent $event, array $scriptProperties)
    {
        extract($scriptProperties);
        switch ($event->name) {
            case 'pdoToolsOnFenomInit':
                /** @var Fenom|FenomX $fenom */
                $fenom->addAllowedFunctions([
                    'array_keys',
                    'array_values',
                ]);
                $fenom->addAccessorSmart('en', 'en', Fenom::ACCESSOR_PROPERTY);
                $fenom->en = $this->modx->getOption('cultureKey') == 'en';

                $fenom->addAccessorSmart('assets_version', 'assets_version', Fenom::ACCESSOR_PROPERTY);
                $fenom->assets_version = $this::assets_version;

                $fenom->addAccessorSmart('assets_url', 'assets_url', Fenom::ACCESSOR_PROPERTY);
                $fenom->assets_url = $this->config['assetsUrl'];

                if ($this->modx->resource) {
                    $fenom->addAccessorSmart('resource', 'resource', Fenom::ACCESSOR_PROPERTY);
                    $resource = $this->modx->resource->toArray();
                    $resource['content'] = $this->modx->resource->getContent();
                    // TV parameters
                    foreach ($resource as $k => $v) {
                        if (is_array($v) && !empty($v[0]) && $k == $v[0]) {
                            $resource[$k] = $this->modx->resource->getTVValue($k);
                        }
                    }
                    $fenom->resource = $resource;
                }

                $fenom->addModifier('units', function ($input, $titles) {
                    return $this->units($input, $titles);
                });

                $fenom->addModifier('primaryParent', function ($input) {
                    return $this->primaryParent($input);
                });

                $fenom->addModifier('getImages', function ($input) {
                    return $this->getImages($input);
                });
                break;

            case 'OnHandleRequest':
                if ($this->modx->context->key == 'mgr') {
                    return;
                }

                // Remove slash and question signs at the end of url
                $uri = $_SERVER['REQUEST_URI'];
                if ($uri != '/' && in_array(substr($uri, -1), ['/', '?'])) {
                    $this->modx->sendRedirect(rtrim($uri, '/?'), ['responseCode' => 'HTTP/1.1 301 Moved Permanently']);
                }

                // Remove .html extension
                if (preg_match('#\.html$#i', $uri)) {
                    $this->modx->sendRedirect(preg_replace('#\.html$#i', '', $uri),
                        ['responseCode' => 'HTTP/1.1 301 Moved Permanently']
                    );
                }

                // Switch context - uncomment it if you have more than one context
                /*
                $c = $this->modx->newQuery('modContextSetting', [
                    'key' => 'http_host',
                    'value' => $_SERVER['HTTP_HOST'],
                ]);
                $c->select('context_key');
                $tstart = microtime(true);
                if ($c->prepare() && $c->stmt->execute()) {
                    $this->modx->queryTime += microtime(true) - $tstart;
                    $this->modx->executedQueries++;
                    if ($context = $c->stmt->fetch(PDO::FETCH_COLUMN)) {
                        if ($context != 'web') {
                            $this->modx->switchContext($context);
                        }
                    }
                }
                */
                break;
            case 'OnLoadWebDocument':
                break;
            case 'OnPageNotFound':
                break;
            case 'OnWebPagePrerender':
                // Compress output html for Google
                $this->modx->resource->_output = preg_replace('#\s+#', ' ', $this->modx->resource->_output);
                break;
        }
    }

    public function units($number, $titles)
    {
        $keys = array(2, 0, 1, 1, 1, 2);
        $mod = $number % 100;
        $suffix_key = ($mod > 7 && $mod < 20) ? 2 : $keys[min($mod % 10, 5)];
        return $titles[$suffix_key];
    }

    public function primaryParent($id)
    {
        $resource = $this->modx->getObject('modResource', $id);

        if ($resource->parent == 0) {
            return $resource->id;
        } else {
            return $this->primaryParent($resource->parent);
        }
    }

    public function getImages($id)
    {
        $thumbs = [
            // 'small',
            // 'medium'
        ];
        $q = $this->modx->newQuery('msProductFile');
        $q->where(array(
            'msProductFile.product_id' => $id,
            'msProductFile.parent' => 0,
            'msProductFile.active' => 1
        ));
        foreach ($thumbs as $thumb) {
            $q->leftJoin('msProductFile', $thumb, $thumb . '.parent = msProductFile.id');
            $q->where(array(
                $thumb . '.path:LIKE' => '%' . $thumb . '%'
            ));
            $q->select($thumb . '.url as ' . $thumb);
        }
        $q->sortby('rank', 'ASC');
        $q->select($this->modx->getSelectColumns('msProductFile', 'msProductFile', ''));
        if ($q->prepare() && $q->stmt->execute()) {
            return $q->stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

}
