<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Domain\Entity\User;
use App\Domain\Entity\ValueObject\Money;
use App\Domain\Entity\Wallet;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Uid\Uuid;

final class WalletFixtures extends Fixture implements DependentFixtureInterface
{
    public const string WALLET_ID_1 = '019b4e2c-80d7-7a56-88ba-927df236e7c8';
    public const string WALLET_ID_2 = '019b4e2c-d971-74a8-b288-24ea9ded9b90';
    public const string WALLET_ID_3 = '019b4e2c-ea60-75df-ab12-a7c08ab1d30e';
    public const string WALLET_ID_4 = '019b4e2c-fa30-7b1f-9c35-bf9a5490e55e';
    public const string WALLET_ID_5 = '019b4e2d-098b-7f3b-a693-4e004ab7e559';
    public const string WALLET_ID_6 = '019b4e2d-1e55-7f7f-ac53-08e8fba07525';
    public const string WALLET_ID_7 = '019b4e2d-2c95-7b60-a78c-23b39884f3c9';
    public const string WALLET_ID_8 = '019b4e2d-3c4f-7cfb-a906-c516640bfceb';
    public const string WALLET_ID_9 = '019b4e2d-4cca-7a9f-9959-0a83bba71db7';
    public const string WALLET_ID_10 = '019b4e2d-622c-7321-9bfd-dadaa32a8c7d';

    public function load(ObjectManager $manager): void
    {
        $wallets = [
            [UserFixtures::USER_ID_1, self::WALLET_ID_1],
            [UserFixtures::USER_ID_2, self::WALLET_ID_2],
            [UserFixtures::USER_ID_3, self::WALLET_ID_3],
            [UserFixtures::USER_ID_4, self::WALLET_ID_4],
            [UserFixtures::USER_ID_5, self::WALLET_ID_5],
            [UserFixtures::USER_ID_6, self::WALLET_ID_6],
            [UserFixtures::USER_ID_7, self::WALLET_ID_7],
            [UserFixtures::USER_ID_8, self::WALLET_ID_8],
            [UserFixtures::USER_ID_9, self::WALLET_ID_9],
            [UserFixtures::USER_ID_10, self::WALLET_ID_10],
        ];

        foreach ($wallets as [$userId, $walletId]) {
            $user = $this->getReference(sprintf('%s-%s', UserFixtures::USER_ID_PREFIX, $userId), User::class);

            $wallet = new Wallet(
                id: Uuid::fromString($walletId),
                user: $user,
                balance: Money::zero(),
            );

            $wallet->deposit(amount: new Money(amountInCents: 100000));

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
