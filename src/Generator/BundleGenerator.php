<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\BundleGeneratorBundle\Generator;

use Pimcore\Bundle\BundleGeneratorBundle\Manipulator\RoutingManipulator;
use Pimcore\Bundle\BundleGeneratorBundle\Model\Bundle;

class BundleGenerator extends BaseBundleGenerator
{
    public function generateBundle(Bundle $bundle)
    {
        parent::generateBundle($bundle);

        $dir = $bundle->getTargetDirectory();

        $parameters = [
            'namespace' => $bundle->getNamespace(),
            'bundle' => $bundle->getName(),
            'format' => $bundle->getConfigurationFormat(),
            'bundle_basename' => $bundle->getBasename(),
            'extension_alias' => $bundle->getExtensionAlias(),
        ];

        $routingFilename = $bundle->getRoutingConfigurationFilename() ?: 'routing.yaml';
        $routingTarget = $dir . '/config/pimcore/' . $routingFilename;

        // create routing file for default annotation
        if ($bundle->getConfigurationFormat() == 'annotation') {
            self::mkdir(dirname($routingTarget));
            self::dump($routingTarget, '');

            $routing = new RoutingManipulator($routingTarget);
            $routing->addResource($bundle->getName(), 'annotation');
        } else {
            // update routing file created by default implementation
            $this->renderFile(
                sprintf('bundle/%s.twig', $routingFilename),
                $dir.'/config/pimcore/'.$routingFilename, $parameters
            );
        }

        $this->renderFile(
            'js/pimcore/startup.js.twig',
            $dir . '/public/js/pimcore/startup.js',
            $parameters
        );
    }
}
