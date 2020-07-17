<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Git;

use RuntimeException;
use Symfony\Component\Process\Exception\RuntimeException as ProcessRuntimeException;
use Symfony\Component\Process\Process;

use function file_exists;
use function Safe\sprintf;
use function sys_get_temp_dir;

final class GitCheckoutRevisionToTemporaryPath implements PerformCheckoutOfRevision
{
    /** @var callable */
    private $uniquenessFunction;

    public function __construct(?callable $uniquenessFunction = null)
    {
        $this->uniquenessFunction = $uniquenessFunction ?? 'uniqid';
    }

    /**
     * {@inheritDoc}
     *
     * @throws ProcessRuntimeException
     */
    public function checkout(CheckedOutRepository $sourceRepository, Revision $revision): CheckedOutRepository
    {
        $checkoutDirectory = $this->generateTemporaryPathFor($revision);

        (new Process(['git', 'clone', $sourceRepository, $checkoutDirectory]))->mustRun();
        (new Process(['git', 'checkout', $revision], $checkoutDirectory))->mustRun();

        return CheckedOutRepository::fromPath($checkoutDirectory);
    }

    /**
     * {@inheritDoc}
     *
     * @throws ProcessRuntimeException
     */
    public function remove(CheckedOutRepository $checkedOutRepository): void
    {
        (new Process(['rm', '-rf', $checkedOutRepository]))->mustRun();
    }

    /**
     * @throws RuntimeException
     */
    private function generateTemporaryPathFor(Revision $revision): string
    {
        $uniquePathGenerator = $this->uniquenessFunction;
        $checkoutDirectory   = sys_get_temp_dir() . '/api-compare-' . $uniquePathGenerator($revision . '_');

        if (file_exists($checkoutDirectory)) {
            throw new RuntimeException(sprintf(
                'Tried to check out revision "%s" to directory "%s" which already exists',
                $revision->__toString(),
                $checkoutDirectory
            ));
        }

        return $checkoutDirectory;
    }
}
