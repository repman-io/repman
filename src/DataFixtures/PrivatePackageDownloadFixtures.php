<?php

declare(strict_types=1);

namespace Buddy\Repman\DataFixtures;

use Buddy\Repman\Entity\Organization\Package\Download;
use Buddy\Repman\Query\Admin\OrganizationQuery;
use Buddy\Repman\Query\Filter;
use Buddy\Repman\Query\User\Model\PackageName;
use Buddy\Repman\Query\User\PackageQuery;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Load download for current private packages:
 * symfony console d:f:l --group=PrivatePackageDownloadFixtures --append.
 */
final class PrivatePackageDownloadFixtures extends Fixture
{
    private OrganizationQuery $organizations;
    private PackageQuery $packages;
    private Generator $faker;
    private EntityManagerInterface $em;

    public function __construct(OrganizationQuery $organizations, PackageQuery $packages, EntityManagerInterface $em)
    {
        $this->organizations = $organizations;
        $this->packages = $packages;
        $this->em = $em;
        $this->faker = Factory::create();
    }

    public function load(ObjectManager $manager): void
    {
        foreach ($this->organizations->findAll(new Filter(0, 100)) as $organization) {
            foreach ($this->packages->getAllNames($organization->id()) as $package) {
                $this->loadDownloads($package);
            }
        }
    }

    private function loadDownloads(PackageName $package): void
    {
        $output = new ConsoleOutput();
        $progress = new ProgressBar($output, 30);
        $output->writeln(sprintf('Package: %s', $package->name()));
        $progress->start();
        $versions = array_map(fn () => $this->faker->numerify('#.#.#'), range(1, 10));

        for ($i = 0; $i < 30; ++$i) {
            $dayDownloads = random_int(0, 100);
            for ($j = 0; $j < $dayDownloads; ++$j) {
                $this->em->persist(new Download(
                    Uuid::uuid4(),
                    Uuid::fromString($package->id()),
                    (new \DateTimeImmutable())->modify(sprintf('-%s days', $i)),
                    $this->faker->randomElement($versions),
                    $this->faker->ipv4,
                    $this->faker->userAgent
                ));
                $this->em->flush();
            }
            $progress->advance();
        }
        $this->em->commit();
        $this->em->beginTransaction();
        $this->em->clear();
        $output->writeln('');
    }
}
