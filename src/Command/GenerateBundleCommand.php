<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\BundleGeneratorBundle\Command;

use Pimcore\Bundle\BundleGeneratorBundle\Generator\BundleGenerator;
use Pimcore\Bundle\BundleGeneratorBundle\Model\Bundle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateBundleCommand extends BaseGenerateBundleCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('pimcore:generate:bundle')
            ->setDescription('Generates a Pimcore bundle')
            ->setHelp(
                <<<EOT
The <info>%command.name%</info> command helps you generates new Pimcore bundles. If you need to create a normal Symfony
bundle, please use the generate:bundle command without pimcore: prefix.

By default, the command interacts with the developer to tweak the generation.
Any passed option will be used as a default value for the interaction
(<comment>--namespace</comment> is the only one needed if you follow the
conventions):

<info>php %command.full_name% --namespace=Acme/BlogBundle</info>

Note that you can use <comment>/</comment> instead of <comment>\\ </comment>for the namespace delimiter to avoid any
problems.

If you want to disable any user interaction, use <comment>--no-interaction</comment> but don't forget to pass all needed options:

<info>php %command.full_name% --namespace=Acme/BlogBundle --dir=src [--bundle-name=...] --no-interaction</info>

Note that the bundle namespace must end with "Bundle".
EOT
            );
    }

    /**
     * @inheritDoc
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $input->setOption('format', 'annotation');

        parent::initialize($input, $output);
    }

    /**
     * @see Command
     *
     * @throws \InvalidArgumentException When namespace doesn't end with Bundle
     * @throws \RuntimeException         When bundle can't be executed
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getQuestionHelper();

        $bundle = $this->createBundleObject($input);
        $bundle->setTestsDirectory($bundle->getTargetDirectory() . '/Tests');

        $questionHelper->writeSection($output, 'Bundle generation');

        /** @var BundleGenerator $generator */
        $generator = $this->getGenerator();

        $output->writeln(sprintf(
            '> Generating a sample bundle skeleton into <info>%s</info>',
            $this->makePathRelative($bundle->getTargetDirectory())
        ));

        $generator->generateBundle($bundle);

        $errors = [];

        $runner = $questionHelper->getRunner($output, $errors);

        // check that the namespace is already auto loaded
        $runner($this->checkAutoloader($output, $bundle), false);
        $runner($this->checkBundleSearchDirectory($bundle), false);


        $questionHelper->writeGeneratorSummary($output, $errors);

        return 0;
    }

    protected function checkBundleSearchDirectory(Bundle $bundle)
    {
        return [
            '- Edit the application configuration and make sure',
            sprintf('  you have added the <comment>%s</comment> to the Pimcore bundle search paths: ', $bundle->getRelativeTargetDirectory()),
            '   <comment>pimcore:</comment>',
            '   <comment>   bundles:</comment>',
            '   <comment>      search_paths:</comment>',
            sprintf('   <comment>          %s</comment>', $bundle->getRelativeTargetDirectory())
        ];
    }

}
