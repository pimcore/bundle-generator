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

namespace Pimcore\Bundle\BundleGeneratorBundle\Manipulator;

use Pimcore\Bundle\BundleGeneratorBundle\Generator\Generator;
use Symfony\Component\DependencyInjection\Container;

/**
 * Changes the PHP code of a YAML routing file.
 *
 * The following class is copied from \Sensio\Bundle\GeneratorBundle\Manipulator\RoutingManipulator
 */
class RoutingManipulator extends Manipulator
{
    private $file;

    /**
     * @param string $file The YAML routing file path
     */
    public function __construct($file)
    {
        $this->file = $file;
    }

    /**
     * Adds a routing resource at the top of the existing ones.
     *
     * @param string $bundle
     * @param string $format
     * @param string $prefix
     * @param string $path
     *
     * @return bool Whether the operation succeeded
     *
     * @throws \RuntimeException If bundle is already imported
     */
    public function addResource($bundle, $format, $prefix = '/', $path = 'routing')
    {
        $current = '';
        $code = sprintf("%s:\n", $this->getImportedResourceYamlKey($bundle, $prefix));

        if (file_exists($this->file)) {
            $current = file_get_contents($this->file);

            // Don't add same bundle twice
            if (false !== strpos($current, '@'.$bundle)) {
                throw new \RuntimeException(sprintf('Bundle "%s" is already imported.', $bundle));
            }
        } elseif (!is_dir($dir = dirname($this->file))) {
            Generator::mkdir($dir);
        }

        if ('annotation' == $format) {
            $code .= sprintf("    resource: \"@%s/src/Controller/\"\n    type:     annotation\n", $bundle);
        } else {
            $code .= sprintf("    resource: \"@%s/Resources/config/%s.%s\"\n", $bundle, $path, $format);
        }
        $code .= sprintf("    prefix:   %s\n", $prefix);
        $code .= "\n";
        $code .= $current;

        if (false === Generator::dump($this->file, $code)) {
            return false;
        }

        return true;
    }

    public function getImportedResourceYamlKey($bundle, $prefix)
    {
        $snakeCasedBundleName = Container::underscore(substr($bundle, 0, -6));
        $routePrefix = self::getRouteNamePrefix($prefix);

        return sprintf('%s%s%s', $snakeCasedBundleName, '' !== $routePrefix ? '_' : '', $routePrefix);
    }

    private static function getRouteNamePrefix($prefix)
    {
        $prefix = preg_replace('/{(.*?)}/', '', $prefix); // {foo}_bar -> _bar
        $prefix = str_replace('/', '_', $prefix);
        $prefix = preg_replace('/_+/', '_', $prefix);     // foo__bar -> foo_bar
        $prefix = trim($prefix, '_');

        return $prefix;
    }
}
