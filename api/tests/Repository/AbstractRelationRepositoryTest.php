<?php

namespace App\Tests\Repository;

use App\Entity\Packages\Architecture;
use App\Entity\Packages\Package;
use App\Entity\Packages\Relations\AbstractRelation;
use App\Entity\Packages\Relations\Dependency;
use App\Entity\Packages\Relations\Provision;
use App\Entity\Packages\Repository;
use App\Repository\AbstractRelationRepository;
use App\Repository\PackageRepository;
use SymfonyDatabaseTest\DatabaseTestCase;

class AbstractRelationRepositoryTest extends DatabaseTestCase
{
    public function testDependencyIsUpdated(): void
    {
        $entityManager = $this->getEntityManager();

        $coreRepository = new Repository('core', Architecture::X86_64);
        $pacman = new Package(
            $coreRepository,
            'pacman',
            '5.0.2-2',
            Architecture::X86_64
        );
        $glibc = new Package(
            $coreRepository,
            'glibc',
            '2.26-10',
            Architecture::X86_64
        );
        $pacman->addDependency(new Dependency('glibc'));
        $entityManager->persist($coreRepository);
        $entityManager->persist($pacman);
        $entityManager->persist($glibc);
        $entityManager->flush();

        /** @var AbstractRelationRepository $abstractRelationRepository */
        $abstractRelationRepository = $entityManager->getRepository(AbstractRelation::class);
        $abstractRelationRepository->updateTargets();

        $entityManager->flush();
        $entityManager->clear();

        /** @var PackageRepository $packageRepository */
        $packageRepository = $entityManager->getRepository(Package::class);
        $databasePacman = $packageRepository->find($pacman->getId());
        /** @var Package $databaseGlibc */
        $databaseGlibc = $databasePacman->getDependencies()->first()->getTarget();
        $this->assertEquals($glibc->getId(), $databaseGlibc->getId());
    }

    public function testProvisionIsUpdated(): void
    {
        $entityManager = $this->getEntityManager();

        $coreRepository = new Repository('core', Architecture::X86_64);
        $pacman = new Package(
            $coreRepository,
            'pacman',
            '5.0.2-2',
            Architecture::X86_64
        );
        $glibc = new Package(
            $coreRepository,
            'glibc-ng',
            '1.0-1',
            Architecture::X86_64
        );
        $glibc->addProvision(new Provision('glibc'));
        $pacman->addDependency(new Dependency('glibc'));
        $entityManager->persist($coreRepository);
        $entityManager->persist($pacman);
        $entityManager->persist($glibc);
        $entityManager->flush();

        /** @var AbstractRelationRepository $abstractRelationRepository */
        $abstractRelationRepository = $entityManager->getRepository(AbstractRelation::class);
        $abstractRelationRepository->updateTargets();

        $entityManager->flush();
        $entityManager->clear();

        /** @var PackageRepository $packageRepository */
        $packageRepository = $entityManager->getRepository(Package::class);
        $databasePacman = $packageRepository->find($pacman->getId());
        /** @var Package $databaseGlibc */
        $databaseGlibc = $databasePacman->getDependencies()->first()->getTarget();
        $this->assertEquals($glibc->getId(), $databaseGlibc->getId());
    }

    public function testDependencyFromAnotherRepositoryIsUpdated(): void
    {
        $entityManager = $this->getEntityManager();

        $coreRepository = new Repository('core', Architecture::X86_64);
        $extraRepository = new Repository('extra', Architecture::X86_64);
        $testingRepository = (new Repository('testing', Architecture::X86_64))->setTesting();
        $pacman = new Package(
            $extraRepository,
            'pacman',
            '5.0.2-2',
            Architecture::X86_64
        );
        $glibc = new Package(
            $coreRepository,
            'glibc',
            '2.26-10',
            Architecture::X86_64
        );
        $testingGlibc = new Package(
            $testingRepository,
            'glibc',
            '3.0-1',
            Architecture::X86_64
        );
        $pacman->addDependency(new Dependency('glibc'));
        $entityManager->persist($coreRepository);
        $entityManager->persist($extraRepository);
        $entityManager->persist($testingRepository);
        $entityManager->persist($pacman);
        $entityManager->persist($glibc);
        $entityManager->persist($testingGlibc);
        $entityManager->flush();

        /** @var AbstractRelationRepository $abstractRelationRepository */
        $abstractRelationRepository = $entityManager->getRepository(AbstractRelation::class);
        $abstractRelationRepository->updateTargets();

        $entityManager->flush();
        $entityManager->clear();

        /** @var PackageRepository $packageRepository */
        $packageRepository = $entityManager->getRepository(Package::class);
        $databasePacman = $packageRepository->find($pacman->getId());
        /** @var Package $databaseGlibc */
        $databaseGlibc = $databasePacman->getDependencies()->first()->getTarget();
        $this->assertEquals($glibc->getId(), $databaseGlibc->getId());
    }

    public function testAmbiguousProvisionIsIgnored(): void
    {
        $entityManager = $this->getEntityManager();

        $coreRepository = new Repository('core', Architecture::X86_64);
        $pacman = new Package(
            $coreRepository,
            'pacman',
            '5.0.2-2',
            Architecture::X86_64
        );
        $glibc4 = new Package(
            $coreRepository,
            'glibc4',
            '4.0-1',
            Architecture::X86_64
        );
        $glibcNg = new Package(
            $coreRepository,
            'glibc-ng',
            '1.0-1',
            Architecture::X86_64
        );
        $glibc4->addProvision(new Provision('glibc'));
        $glibcNg->addProvision(new Provision('glibc'));
        $pacman->addDependency(new Dependency('glibc'));
        $entityManager->persist($coreRepository);
        $entityManager->persist($pacman);
        $entityManager->persist($glibc4);
        $entityManager->persist($glibcNg);
        $entityManager->flush();

        /** @var AbstractRelationRepository $abstractRelationRepository */
        $abstractRelationRepository = $entityManager->getRepository(AbstractRelation::class);
        $abstractRelationRepository->updateTargets();

        $entityManager->flush();
        $entityManager->clear();

        /** @var PackageRepository $packageRepository */
        $packageRepository = $entityManager->getRepository(Package::class);
        $databasePacman = $packageRepository->find($pacman->getId());
        $databaseGlibc = $databasePacman->getDependencies()->first()->getTarget();
        $this->assertNull($databaseGlibc);
    }

    public function testProvisionHasCorrectArchitecture(): void
    {
        $entityManager = $this->getEntityManager();

        $core64Repository = new Repository('core', Architecture::X86_64);
        $core32Repository = new Repository('core', Architecture::I686);
        $pacman = new Package(
            $core64Repository,
            'pacman',
            '5.0.2-2',
            Architecture::X86_64
        );
        $glibcNg32 = new Package(
            $core32Repository,
            'glibc-ng',
            '1.0-1',
            Architecture::I686
        );
        $glibcNg64 = new Package(
            $core64Repository,
            'glibc-ng',
            '1.0-1',
            Architecture::X86_64
        );
        $glibcNg32->addProvision(new Provision('glibc'));
        $glibcNg64->addProvision(new Provision('glibc'));
        $pacman->addDependency(new Dependency('glibc'));
        $entityManager->persist($core64Repository);
        $entityManager->persist($core32Repository);
        $entityManager->persist($pacman);
        $entityManager->persist($glibcNg32);
        $entityManager->persist($glibcNg64);
        $entityManager->flush();

        /** @var AbstractRelationRepository $abstractRelationRepository */
        $abstractRelationRepository = $entityManager->getRepository(AbstractRelation::class);
        $abstractRelationRepository->updateTargets();

        $entityManager->flush();
        $entityManager->clear();

        /** @var PackageRepository $packageRepository */
        $packageRepository = $entityManager->getRepository(Package::class);
        $databasePacman = $packageRepository->find($pacman->getId());
        /** @var Package $databaseGlibc */
        $databaseGlibc = $databasePacman->getDependencies()->first()->getTarget();
        $this->assertEquals($glibcNg64->getId(), $databaseGlibc->getId());
    }

    public function testFindWithTargets(): void
    {
        $entityManager = $this->getEntityManager();

        $coreRepository = new Repository('core', Architecture::X86_64);
        $pacman = new Package(
            $coreRepository,
            'pacman',
            '5.0.2-2',
            Architecture::X86_64
        );
        $glibc = new Package(
            $coreRepository,
            'glibc',
            '2.26-10',
            Architecture::X86_64
        );
        $pacman->addDependency(new Dependency('glibc'));
        $entityManager->persist($coreRepository);
        $entityManager->persist($pacman);
        $entityManager->persist($glibc);
        $entityManager->flush();

        /** @var AbstractRelationRepository $abstractRelationRepository */
        $abstractRelationRepository = $entityManager->getRepository(AbstractRelation::class);
        $abstractRelationRepository->updateTargets();

        $entityManager->flush();
        $entityManager->clear();

        $dependencies = $abstractRelationRepository->findWithTargets();

        $this->assertCount(1, $dependencies);
        $this->assertNotNull($dependencies[0]->getTarget());
        $this->assertEquals($glibc->getId(), $dependencies[0]->getTarget()->getId());
    }
}
