<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Domain\Entity\User;
use App\Domain\Entity\Wallet;
use App\Domain\Entity\ValueObject\Money;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Uid\Uuid;

final class WalletFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $userIds = [
            UserFixtures::USER_ID_1,
            UserFixtures::USER_ID_2,
            UserFixtures::USER_ID_3,
            UserFixtures::USER_ID_4,
            UserFixtures::USER_ID_5,
            UserFixtures::USER_ID_6,
            UserFixtures::USER_ID_7,
            UserFixtures::USER_ID_8,
            UserFixtures::USER_ID_9,
            UserFixtures::USER_ID_10,
        ];

        foreach ($userIds as $userIdString) {
            /** @var User $user */
            $user = $manager->getRepository(User::class)->find(Uuid::fromString($userIdString));

            if (!$user instanceof User) {
                continue;
            }

            $wallet = new Wallet(
                id: Uuid::v7(),
                user: $user,
                balance: Money::zero(),
            );

            $manager->persist($wallet);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}
