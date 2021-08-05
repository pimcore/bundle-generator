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

namespace Pimcore\Bundle\BundleGeneratorBundle\Command;

use Pimcore\Bundle\BundleGeneratorBundle\Generator\BundleGenerator;
use Pimcore\Bundle\BundleGeneratorBundle\Model\Bundle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Generates bundles.
 *
 * The following class is copied from \Sensio\Bundle\BundleGeneratorBundle\Command\GenerateBundleCommand
 */
class BaseGenerateBundleCommand extends BaseGeneratorCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('generate:bundle')
            ->setDescription('Generates a bundle')
            ->setDefinition([
                new InputOption('namespace', '', InputOption::VALUE_REQUIRED, 'The namespace of the bundle to create'),
                new InputOption('dir', '', InputOption::VALUE_REQUIRED, 'The directory where to create the bundle', 'bundles/'),
                new InputOption('bundle-name', '', InputOption::VALUE_REQUIRED, 'The optional bundle name'),
                new InputOption('format', '', InputOption::VALUE_REQUIRED, 'Use the format for configuration files (php, xml, yaml, or annotation)'),
                new InputOption('shared', '', InputOption::VALUE_NONE, 'Are you planning on sharing this bundle across multiple applications?'),
            ])
            ->setHelp(<<<EOT
The <info>%command.name%</info> command helps you generates new bundles.

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
            )
        ;
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

        // register the bundle in the Kernel class
        $runner($this->updateKernel($this->getContainer()->get('kernel'), $bundle), false);

        // routing importing
        $runner($this->updateRouting($bundle), false);

        if (!$bundle->shouldGenerateDependencyInjectionDirectory()) {
            // we need to import their services.yaml manually!
            $runner($this->updateConfiguration($output, $bundle));
        }

        $questionHelper->writeGeneratorSummary($output, $errors);

        return 0;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getQuestionHelper();
        $questionHelper->writeSection($output, 'Welcome to the Symfony bundle generator!');

        /*
         * shared option
         */
        $shared = $input->getOption('shared');
        // ask, but use $shared as the default
        $question = new ConfirmationQuestion($questionHelper->getQuestion(
            'Are you planning on sharing this bundle across multiple applications?',
            $shared ? 'yes' : 'no'
        ), $shared);
        $shared = $questionHelper->ask($input, $output, $question);
        $input->setOption('shared', $shared);

        /*
         * namespace option
         */
        $namespace = $input->getOption('namespace');
        $output->writeln([
            '',
            'Your application code must be written in <comment>bundles</comment>. This command helps',
            'you generate them easily.',
            '',
        ]);

        $askForBundleName = true;
        if ($shared) {
            // a shared bundle, so it should probably have a vendor namespace
            $output->writeln([
                'Each bundle is hosted under a namespace (like <comment>Acme/BlogBundle</comment>).',
                'The namespace should begin with a "vendor" name like your company name, your',
                'project name, or your client name, followed by one or more optional category',
                'sub-namespaces, and it should end with the bundle name itself',
                '(which must have <comment>Bundle</comment> as a suffix).',
                '',
                'See http://symfony.com/doc/current/cookbook/bundles/best_practices.html#bundle-name for more',
                'details on bundle naming conventions.',
                '',
                'Use <comment>/</comment> instead of <comment>\\ </comment>for the namespace delimiter to avoid any problems.',
                '',
            ]);

            $question = new Question($questionHelper->getQuestion(
                'Bundle namespace',
                $namespace
            ), $namespace);
            $question->setValidator(function ($answer) {
                return Validators::validateBundleNamespace($answer, true);
            });
            $namespace = $questionHelper->ask($input, $output, $question);
        } else {
            // a simple application bundle
            $output->writeln([
                'Give your bundle a descriptive name, like <comment>BlogBundle</comment>.',
            ]);

            $question = new Question($questionHelper->getQuestion(
                'Bundle name',
                $namespace
            ), $namespace);
            $question->setValidator(function ($inputNamespace) {
                return Validators::validateBundleNamespace($inputNamespace, false);
            });
            $namespace = $questionHelper->ask($input, $output, $question);

            if (strpos($namespace, '\\') === false) {
                // this is a bundle name (FooBundle) not a namespace (Acme\FooBundle)
                // so this is the bundle name (and it is also the namespace)
                $input->setOption('bundle-name', $namespace);
                $askForBundleName = false;
            }
        }
        $input->setOption('namespace', $namespace);

        /*
         * bundle-name option
         */
        if ($askForBundleName) {
            $bundle = $input->getOption('bundle-name');
            // no bundle yet? Get a default from the namespace
            if (!$bundle) {
                $bundle = strtr($namespace, ['\\Bundle\\' => '', '\\' => '']);
            }

            $output->writeln([
                '',
                'In your code, a bundle is often referenced by its name. It can be the',
                'concatenation of all namespace parts but it\'s really up to you to come',
                'up with a unique name (a good practice is to start with the vendor name).',
                'Based on the namespace, we suggest <comment>'.$bundle.'</comment>.',
                '',
            ]);
            $question = new Question($questionHelper->getQuestion(
                'Bundle name',
                $bundle
            ), $bundle);
            $question->setValidator(
                ['Pimcore\Bundle\BundleGeneratorBundle\Command\Validators', 'validateBundleName']
            );
            $bundle = $questionHelper->ask($input, $output, $question);
            $input->setOption('bundle-name', $bundle);
        }

        /*
         * dir option
         */
        // defaults to src/ in the option
        $dir = $input->getOption('dir');
        $output->writeln([
            '',
            'We recommend adding bundles to a <info>bundles/</info> directory. Unless you\'re',
            'doing something custom, hit enter to keep this default!',
            '',
        ]);

        $question = new Question($questionHelper->getQuestion(
            'Target Directory',
            $dir
        ), $dir);
        $dir = $questionHelper->ask($input, $output, $question);
        $input->setOption('dir', $dir);

        /*
         * format option
         */
        $format = $input->getOption('format');
        if (!$format) {
            $format = $shared ? 'xml' : 'annotation';
        }
        $output->writeln([
            '',
            'What format do you want to use for your generated configuration?',
            '',
        ]);

        $question = new Question($questionHelper->getQuestion(
            'Configuration format (annotation, yaml, xml, php)',
            $format
        ), $format);
        $question->setValidator(function ($format) {
            return Validators::validateFormat($format);
        });
        $question->setAutocompleterValues(['annotation', 'yaml', 'xml', 'php']);
        $format = $questionHelper->ask($input, $output, $question);
        $input->setOption('format', $format);
    }

    protected function checkAutoloader(OutputInterface $output, Bundle $bundle)
    {
        $output->writeln('> Checking that the bundle is autoloaded');
        if (!class_exists($bundle->getBundleClassName())) {
            return [
                '- Edit the <comment>composer.json</comment> file and register the bundle',
                '  namespace in the "autoload:psr-4" section and run <comment>composer dump-autoload</comment>:',
                sprintf('  <comment>"%s\\\\": "%s"</comment>', str_replace('\\', '\\\\', $bundle->getNamespace()), $bundle->getRelativeTargetDirectory()),
            ];
        }
    }

    protected function updateKernel(KernelInterface $kernel, Bundle $bundle)
    {
        $reflected = new \ReflectionObject($kernel);

        return [
            sprintf('- Edit <comment>%s</comment>', $reflected->getFilename()),
            '  and add the following bundle in the <comment>Kernel::registerBundlesToCollection()</comment> method:',
            '',
            sprintf('    <comment>$collection->addBundle(%s::class);,</comment>', $bundle->getBundleClassName()),
            '',
        ];
    }

    protected function updateRouting(Bundle $bundle)
    {
        if ('annotation' === $bundle->getConfigurationFormat()) {
            $help = sprintf("        <comment>resource: \"@%s/Controller/\"</comment>\n        <comment>type:     annotation</comment>\n", $bundle->getName());
        } else {
            $help = sprintf("        <comment>resource: \"@%s/Resources/config/routing.%s\"</comment>\n", $bundle->getName(), $bundle->getConfigurationFormat());
        }
        $help .= "        <comment>prefix:   /</comment>\n";

        return [
            '- Import the bundle\'s routing resource in the app\'s main routing file:',
            '',
            sprintf('    <comment>%s:</comment>', $bundle->getName()),
            $help,
            '',
        ];
    }

    protected function updateConfiguration(OutputInterface $output, Bundle $bundle)
    {
        $importCode = sprintf(<<<EOF
    - { resource: "@%s/Resources/config/%s" }
EOF
            ,
            $bundle->getName(),
            $bundle->getServicesConfigurationFilename()
        );

        return [
            sprintf('- Import the bundle\'s "%s" resource in the app\'s main configuration file:', $bundle->getServicesConfigurationFilename()),
            '',
            $importCode,
            '',
        ];
    }

    /**
     * Creates the Bundle object based on the user's (non-interactive) input.
     *
     * @param InputInterface $input
     *
     * @return Bundle
     */
    protected function createBundleObject(InputInterface $input)
    {
        foreach (['namespace', 'dir'] as $option) {
            if (null === $input->getOption($option)) {
                throw new \RuntimeException(sprintf('The "%s" option must be provided.', $option));
            }
        }

        $shared = $input->getOption('shared');

        $namespace = Validators::validateBundleNamespace($input->getOption('namespace'), $shared);
        if (!$bundleName = $input->getOption('bundle-name')) {
            $bundleName = strtr($namespace, ['\\' => '']);
        }
        $bundleName = Validators::validateBundleName($bundleName);
        $dir = $input->getOption('dir');
        if (null === $input->getOption('format')) {
            $input->setOption('format', 'annotation');
        }
        $format = Validators::validateFormat($input->getOption('format'));

        // an assumption that the kernel root dir is in a directory (like app/)
        $projectRootDirectory = $this->getContainer()->getParameter('kernel.project_dir');

        if (!$this->getContainer()->get('filesystem')->isAbsolutePath($dir)) {
            $dir = $projectRootDirectory.'/'.$dir;
        }
        // add trailing / if necessary
        $dir = '/' === substr($dir, -1, 1) ? $dir : $dir.'/';

        $bundle = new Bundle(
            $namespace,
            $bundleName,
            $dir,
            $format,
            $shared
        );

        // not shared - put the tests in the root
        if (!$shared) {
            $testsDir = $projectRootDirectory.'/tests/'.$bundleName;
            $bundle->setTestsDirectory($testsDir);
        }

        return $bundle;
    }

    protected function createGenerator()
    {
        return new BundleGenerator();
    }
}
