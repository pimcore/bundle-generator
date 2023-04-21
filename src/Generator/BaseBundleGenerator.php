<?php

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

use Pimcore\Bundle\BundleGeneratorBundle\Model\Bundle;

/**
 * Generates a bundle.
 *
 * The following class is copied from \Sensio\Bundle\BundleGeneratorBundle\Generator\BundleGenerator
 */
class BaseBundleGenerator extends Generator
{
    public function generateBundle(Bundle $bundle)
    {
        $dir = $bundle->getTargetDirectory();

        if (file_exists($dir)) {
            if (!is_dir($dir)) {
                throw new \RuntimeException(sprintf('Unable to generate the bundle as the target directory "%s" exists but is a file.', realpath($dir)));
            }
            $files = scandir($dir);
            if ($files != ['.', '..']) {
                throw new \RuntimeException(sprintf('Unable to generate the bundle as the target directory "%s" is not empty.', realpath($dir)));
            }
            if (!is_writable($dir)) {
                throw new \RuntimeException(sprintf('Unable to generate the bundle as the target directory "%s" is not writable.', realpath($dir)));
            }
        }

        $parameters = [
            'namespace' => $bundle->getNamespace(),
            'bundle' => $bundle->getName(),
            'format' => $bundle->getConfigurationFormat(),
            'bundle_basename' => $bundle->getBasename(),
            'extension_alias' => $bundle->getExtensionAlias(),
        ];

        $this->renderFile('bundle/Bundle.php.twig', $dir.'/src/'.$bundle->getName().'.php', $parameters);
        if ($bundle->shouldGenerateDependencyInjectionDirectory()) {
            $this->renderFile('bundle/Extension.php.twig', $dir.'/src/DependencyInjection/'.$bundle->getBasename().'Extension.php', $parameters);
            $this->renderFile('bundle/Configuration.php.twig', $dir.'/src/DependencyInjection/Configuration.php', $parameters);
        }
        $this->renderFile('bundle/DefaultController.php.twig', $dir.'/src/Controller/DefaultController.php', $parameters);
        $this->renderFile('bundle/DefaultControllerTest.php.twig', $bundle->getTestsDirectory().'/Controller/DefaultControllerTest.php', $parameters);

        // render the services.yaml/xml file
        $servicesFilename = $bundle->getServicesConfigurationFilename();
        $this->renderFile(
            sprintf('bundle/%s.twig', $servicesFilename),
            $dir.'/config/'.$servicesFilename, $parameters
        );

        if ($routingFilename = $bundle->getRoutingConfigurationFilename()) {
            $this->renderFile(
                sprintf('bundle/%s.twig', $routingFilename),
                $dir.'/config/pimcore/'.$routingFilename, $parameters
            );
        }
    }
}
