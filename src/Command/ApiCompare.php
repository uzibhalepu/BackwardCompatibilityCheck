<?php
declare(strict_types=1);

namespace Roave\ApiCompare\Command;

use Assert\Assert;
use Roave\ApiCompare\Comparator;
use Roave\ApiCompare\Factory\DirectoryReflectorFactory;
use Roave\ApiCompare\Formatter\SymfonyConsoleTextFormatter;
use Roave\ApiCompare\Git\CheckedOutRepository;
use Roave\ApiCompare\Git\ParseRevision;
use Roave\ApiCompare\Git\PerformCheckoutOfRevision;
use Roave\ApiCompare\Git\PickVersionFromVersionCollection;
use Roave\ApiCompare\Git\Revision;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Version\VersionsCollection;

final class ApiCompare extends Command
{
    /** @var PerformCheckoutOfRevision */
    private $git;

    /**
     * @var DirectoryReflectorFactory
     */
    private $reflectorFactory;

    /**
     * @var ParseRevision
     */
    private $parseRevision;

    /**
     * @var PickVersionFromVersionCollection
     */
    private $pickFromVersion;

    /**
     * @param PerformCheckoutOfRevision $git
     * @param DirectoryReflectorFactory $reflectorFactory
     * @param ParseRevision $parseRevision
     * @param PickVersionFromVersionCollection $pickFromVersion
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(
        PerformCheckoutOfRevision $git,
        DirectoryReflectorFactory $reflectorFactory,
        ParseRevision $parseRevision,
        PickVersionFromVersionCollection $pickFromVersion
    ) {
        parent::__construct();
        $this->git = $git;
        $this->reflectorFactory = $reflectorFactory;
        $this->parseRevision = $parseRevision;
        $this->pickFromVersion = $pickFromVersion;
    }

    /**
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure() : void
    {
        $this
            ->setName('api-compare:compare')
            ->setDescription('List comparisons between class APIs')
            ->addOption('from', null, InputOption::VALUE_OPTIONAL)
            ->addOption('to', null, InputOption::VALUE_REQUIRED, '', 'HEAD')
            ->addArgument(
                'sources-path',
                InputArgument::OPTIONAL,
                'Path to the sources, relative to the repository root',
                'src'
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \Symfony\Component\Process\Exception\LogicException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \Roave\BetterReflection\SourceLocator\Exception\InvalidFileInfo
     * @throws \Roave\BetterReflection\SourceLocator\Exception\InvalidDirectory
     */
    public function execute(InputInterface $input, OutputInterface $output) : void
    {
        // @todo fix flaky assumption about the path of the source repo...
        $sourceRepo = CheckedOutRepository::fromPath(getcwd());

        $fromPath = $this->git->checkout(
            $sourceRepo,
            $input->hasOption('from') && null !== $input->getOption('from')
                ? $this->parseRevisionFromInput($input, $sourceRepo)
                : $this->determineFromRevisionFromRepository($sourceRepo)
        );
        $toPath = $this->git->checkout($sourceRepo, Revision::fromSha1($input->getOption('to')));
        $sourcesPath = $input->getArgument('sources-path');

        // @todo fix hard-coded /src/ addition...
        try {
            $fromSources = $fromPath . '/' . $sourcesPath;
            $toSources   = $toPath . '/' . $sourcesPath;

            Assert::that($fromSources)->directory();
            Assert::that($toSources)->directory();

            (new SymfonyConsoleTextFormatter($output))->write(
                (new Comparator())->compare(
                    $this->reflectorFactory->__invoke((string)$fromPath . '/' . $sourcesPath),
                    $this->reflectorFactory->__invoke((string)$toPath . '/' . $sourcesPath)
                )
            );
        } finally {
            $this->git->remove($fromPath);
            $this->git->remove($toPath);
        }
    }

    /**
     * @param InputInterface $input
     * @param CheckedOutRepository $repository
     * @return Revision
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    private function parseRevisionFromInput(InputInterface $input, CheckedOutRepository $repository) : Revision
    {
        return $this->parseRevision->fromStringForRepository(
            (string)$input->getOption('from'),
            $repository
        );
    }

    /**
     * @param CheckedOutRepository $repository
     * @return Revision
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @throws \Symfony\Component\Process\Exception\LogicException
     */
    private function determineFromRevisionFromRepository(CheckedOutRepository $repository) : Revision
    {
        $tags = $this->grabListOfTagsFromRepository($repository);
        return $this->parseRevision->fromStringForRepository(
            $this->pickFromVersion->forVersions($tags)->getVersionString(),
            $repository
        );
    }

    /**
     * {@inheritDoc}
     * @throws \Symfony\Component\Process\Exception\LogicException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     */
    private function grabListOfTagsFromRepository(CheckedOutRepository $checkedOutRepository) : VersionsCollection
    {
        $output = (new Process(['git', 'tag', '-l']))
            ->setWorkingDirectory((string)$checkedOutRepository)
            ->mustRun()
            ->getOutput();

        return VersionsCollection::fromArray(array_filter(
            explode("\n", $output),
            function (string $maybeVersion) {
                return trim($maybeVersion) !== '';
            }
        ));
    }
}
