<?php

namespace Pimcore\Bundle\BundleGeneratorBundle\Command;

use Pimcore\Bundle\BundleGeneratorBundle\Command\Helper\QuestionHelper;
use Pimcore\Bundle\BundleGeneratorBundle\Generator\Generator;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * Base class for generator commands.
 *
 * The following class is copied from \Sensio\Bundle\BundleGeneratorBundle\Command\GeneratorCommand
 */
abstract class BaseGeneratorCommand extends Command
{
    /**
     * @var Generator
     */
    private $generator;

    abstract protected function createGenerator();

    protected function getGenerator(BundleInterface $bundle = null)
    {
        if (null === $this->generator) {
            $this->generator = $this->createGenerator();
            $this->generator->setSkeletonDirs($this->getSkeletonDirs($bundle));
        }

        return $this->generator;
    }

    protected $container;

    protected function getContainer()
    {
        if (null === $this->container) {
            $application = $this->getApplication();
            if (null === $application) {
                throw new \LogicException('The container cannot be retrieved as the application instance is not yet set.');
            }

            $this->container = $application->getKernel()->getContainer();
        }

        return $this->container;
    }


    protected function getSkeletonDirs(BundleInterface $bundle = null)
    {
        $skeletonDirs = [];

        if (isset($bundle) && is_dir($dir = $bundle->getPath().'/Resources/GeneratorBundle/skeleton')) {
            $skeletonDirs[] = $dir;
        }

        if (is_dir($dir = $this->getContainer()->get('kernel')->getRootdir().'/Resources/GeneratorBundle/skeleton')) {
            $skeletonDirs[] = $dir;
        }

        $skeletonDirs[] = __DIR__.'/../Resources/skeleton';
        $skeletonDirs[] = __DIR__.'/../Resources';

        return $skeletonDirs;
    }

    protected function getQuestionHelper()
    {
        $question = $this->getHelperSet()->get('question');
        if (!$question || get_class($question) !== QuestionHelper::class) {
            $this->getHelperSet()->set($question = new QuestionHelper());
        }

        return $question;
    }

    /**
     * Tries to make a path relative to the project, which prints nicer.
     *
     * @param string $absolutePath
     *
     * @return string
     */
    protected function makePathRelative($absolutePath)
    {
        $projectRootDir = dirname($this->getContainer()->getParameter('kernel.project_dir'));

        return str_replace($projectRootDir.'/', '', realpath($absolutePath) ?: $absolutePath);
    }
}
