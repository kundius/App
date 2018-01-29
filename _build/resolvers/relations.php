<?php
/** @var xPDOTransport $transport */
/** @var array $options */
/** @var modX $modx */
if ($transport->xpdo) {
    $modx =& $transport->xpdo;

    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
        case xPDOTransport::ACTION_UPGRADE:
            // relations for template vars
            $templates = $modx->getIterator('modTemplate');
            /** @var modTemplate $template */
            foreach ($templates as $template) {
                // remove old relations
                foreach($modx->getIterator('modTemplateVarTemplate', [
                    'templateid' => $template->id
                ]) as $tvt) $tvt->remove();

                $properties = $template->get('properties');
                
                // continue if empty
                if(empty($properties['tmplvars'])) continue;
                
                // add new relations
                foreach($properties['tmplvars'] as $name) {
                    if($tv = $modx->getObject('modTemplateVar', ['name' => $name])) {
                        $tvt = $modx->newObject('modTemplateVarTemplate');
                        $tvt->set('tmplvarid', $tv->id);
                        $tvt->set('templateid', $template->id);
                        $tvt->save();
                    }
                }

                // remove tvs list
                unset($properties['tmplvars']);
                $template->set('properties', $properties);
                $template->save();
            }

            // relations for template of resources
            $resources = $modx->getIterator('modResource');
            /** @var modResource $resource */
            foreach ($resources as $resource) {
                $properties = $resource->get('properties');

                // update template
                if(!empty($properties['template'])) {
                    if($template = $modx->getObject('modTemplate', [
                        'templatename' => $properties['template']
                    ])) {
                        unset($properties['template']);
                        $resource->set('template', $template->id);
                        $resource->set('properties', $properties);
                        $resource->save();
                    }
                }

                // update parent
                if(!empty($properties['parent'])) {
                    if($parent = $modx->getObject('modResource', [
                        'uri' => $properties['parent']
                    ])) {
                        unset($properties['parent']);
                        $resource->set('parent', $parent->id);
                        $resource->set('properties', $properties);
                        $resource->save();
                    }
                }
            }
            break;
    }

}

return true;
