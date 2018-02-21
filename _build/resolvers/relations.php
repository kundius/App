<?php
/** @var xPDOTransport $transport */
/** @var array $options */
/** @var modX $modx */
if ($transport->xpdo) {
    $modx =& $transport->xpdo;

    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
        case xPDOTransport::ACTION_UPGRADE:
            // relations for template of resources
            $resources = $modx->getIterator('modResource');
            /** @var modResource $resource */
            foreach ($resources as $resource) {
                $properties = $resource->get('properties');

                // update tickets template
                if(!empty($properties['tickets']) && !empty($properties['tickets']['template'])) {
                    if($template = $modx->getObject('modTemplate', [
                        'templatename' => $properties['tickets']['template']
                    ])) {
                        $properties['tickets']['template'] = $template->id;
                        $resource->set('properties', $properties);
                        $resource->save();
                    }
                }
            }
            break;
    }

}

return true;