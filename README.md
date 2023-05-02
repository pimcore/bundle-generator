# Pimcore Bundle Generator 

This bundle provides a bundle generator for Pimcore to generate bundle skeletons and give you a head start
for bundle development. 

To generate bundle skeletons just install and activate this bundle, use one of the two commands provided and 
follow the instructions:  
- `bundle:generate` - to generate default symfony bundles
- `pimcore:generate:bundle` - to generate Pimcore bundles

# Installation

1. On your Pimcore 11 root project

```bash
$ composer require pimcore/bundle-generator
```

2. Make sure the bundle is enabled in the `config/bundles.php` file. The following lines should be added:

```bash
use Pimcore\Bundle\BundleGeneratorBundle\PimcoreBundleGeneratorBundle;

return [
    PimcoreBundleGeneratorBundle::class => ['all' => true],
];
```

Go to your terminal/command prompt, And you're ready to rock !

## Contributions
As Pimcore Bundle Generator is a community project, any contributions highly appreciated.
For details see our [Contributing guide](https://github.com/pimcore/bundle-generator/blob/master/CONTRIBUTING.md).
