<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Domain\Entity\Enum\UserType;
use App\Domain\Entity\User;
use App\Domain\Entity\ValueObject\Document;
use App\Domain\Entity\ValueObject\Email;
use App\Domain\Entity\ValueObject\PasswordHash;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Uid\Uuid;

final class UserFixtures extends Fixture
{
    public const string USER_ID_1 = '019b4deb-735f-7b74-924b-4d2311c76edd';
    public const string USER_ID_2 = '019b4deb-6194-7784-b219-3ddc079928ab';
    public const string USER_ID_3 = '019b4deb-4cad-7e0b-b000-052f54a76458';
    public const string USER_ID_4 = '019b4deb-3cb0-7cdb-ba19-e3327c537232';
    public const string USER_ID_5 = '019b4deb-29f9-72b1-92a4-ef6d933db291';
    public const string USER_ID_6 = '019b4deb-1a4b-7ebb-8ffc-3c2abb629574';
    public const string USER_ID_7 = '019b4deb-0929-7eef-9665-ea87cd6b5a53';
    public const string USER_ID_8 = '019b4dea-f89c-760e-bba0-e46e2c6f669c';
    public const string USER_ID_9 = '019b4dea-e556-7695-9e46-da8ed728bac0';
    public const string USER_ID_10 = '019b4dea-d01a-7982-9894-d73c9dffa9fc';

    public function load(ObjectManager $manager): void
    {
        $hash = static fn (string $plain): PasswordHash => PasswordHash::fromHash(
            password_hash($plain, PASSWORD_BCRYPT),
        );

        $usersData = [
            [
                'id' => self::USER_ID_1,
                'fullName' => 'Alan Turing',
                'email' => 'alan.turing@example.com',
                'document' => '11111111111',
                'type' => UserType::COMMON,
            ],
            [
                'id' => self::USER_ID_2,
                'fullName' => 'Grace Hopper',
                'email' => 'grace.hopper@example.com',
                'document' => '22222222222',
                'type' => UserType::COMMON,
            ],
            [
                'id' => self::USER_ID_3,
                'fullName' => 'Edsger Dijkstra',
                'email' => 'edsger.dijkstra@example.com',
                'document' => '33333333333',
                'type' => UserType::COMMON,
            ],
            [
                'id' => self::USER_ID_4,
                'fullName' => 'Donald Knuth',
                'email' => 'donald.knuth@example.com',
                'document' => '44444444444',
                'type' => UserType::COMMON,
            ],
            [
                'id' => self::USER_ID_5,
                'fullName' => 'Barbara Liskov',
                'email' => 'barbara.liskov@example.com',
                'document' => '55555555555',
                'type' => UserType::COMMON,
            ],
            [
                'id' => self::USER_ID_6,
                'fullName' => 'Linus Torvalds',
                'email' => 'linus.torvalds@example.com',
                'document' => '66666666666',
                'type' => UserType::COMMON,
            ],
            [
                'id' => self::USER_ID_7,
                'fullName' => 'Guido van Rossum',
                'email' => 'guido.vanrossum@example.com',
                'document' => '77777777777',
                'type' => UserType::COMMON,
            ],
            [
                'id' => self::USER_ID_8,
                'fullName' => 'Ada Lovelace',
                'email' => 'ada.lovelace@example.com',
                'document' => '88888888888',
                'type' => UserType::MERCHANT,
            ],
            [
                'id' => self::USER_ID_9,
                'fullName' => 'Niklaus Wirth',
                'email' => 'niklaus.wirth@example.com',
                'document' => '99999999999',
                'type' => UserType::MERCHANT,
            ],
            [
                'id' => self::USER_ID_10,
                'fullName' => 'Dennis Ritchie',
                'email' => 'dennis.ritchie@example.com',
                'document' => '10101010101',
                'type' => UserType::MERCHANT,
            ],
        ];

        foreach ($usersData as $data) {
            $user = new User(
                id: Uuid::fromString($data['id']),
                fullName: $data['fullName'],
                document: new Document($data['document']),
                email: new Email($data['email']),
                passwordHash: $hash(plain: 'password123'),
                type: $data['type'],
            );

            $manager->persist($user);
        }

        $manager->flush();
    }
}
