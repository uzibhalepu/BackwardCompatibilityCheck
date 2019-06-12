<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Git;

use Assert\Assert;
use Symfony\Component\Process\Exception\LogicException;
use Symfony\Component\Process\Exception\RuntimeException;
use Version\Constraint\ComparisonConstraint;
use Version\Constraint\CompositeConstraint;
use Version\Constraint\ConstraintInterface;
use Version\Version;
use Version\VersionsCollection;

final class PickLastMinorVersionFromCollection implements PickVersionFromVersionCollection
{
    /**
     * {@inheritDoc}
     *
     * @throws LogicException
     * @throws RuntimeException
     */
    public function forVersions(VersionsCollection $versions) : Version
    {
        Assert::that($versions->count())
            ->greaterThan(0, 'Cannot determine latest minor version from an empty collection');

        $stableVersions = $versions->matching(new class implements ConstraintInterface {
            public function assert(Version $version) : bool
            {
                return ! $version->isPreRelease();
            }
        });

        $versionsSortedDescending = $stableVersions->sortedDescending();

        $lastVersion = $versionsSortedDescending->first();

        $matchingMinorVersions = $stableVersions
            ->matching(new CompositeConstraint(
                CompositeConstraint::OPERATOR_AND,
                new ComparisonConstraint(ComparisonConstraint::OPERATOR_LTE, $lastVersion),
                new ComparisonConstraint(
                    ComparisonConstraint::OPERATOR_GTE,
                    Version::fromString($lastVersion->getMajor() . '.' . $lastVersion->getMinor() . '.0')
                )
            ))
            ->sortedAscending();

        return $matchingMinorVersions->first();
    }
}
