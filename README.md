# Pimcore Bundle Generator 

This bundle provides a bundle generator for Pimcore to generate bundle skeletons and give you a head start
for bundle development. 

To generate bundle skeletons just install and activate this bundle, use one of the two commands provided and 
follow the instructions:  
- `bundle:generate` - to generate default symfony bundles
- `pimcore:generate:bundle` - to generate Pimcore bundles

# Installation

On your Pimcore X root project

```bash
$ composer -vvv require pimcore/bundle-generator
```

After that at `src/Kernel.php` on `registerBundlesToCollection` function register the bundle by using the following code snippet :

```php
if (class_exists(\Pimcore\Bundle\BundleGeneratorBundle\PimcoreBundleGeneratorBundle::class)) {
    $collection->addBundle(new \Pimcore\Bundle\BundleGeneratorBundle\PimcoreBundleGeneratorBundle);
}
```

Go to your terminal/command prompt, And you're ready to rock !

## Contributions
As Pimcore Bundle Generator is a community project, any contributions highly appreciated.
For details see our [Contributing guide](https://github.com/pimcore/bundle-generator/blob/master/CONTRIBUTING.md).
