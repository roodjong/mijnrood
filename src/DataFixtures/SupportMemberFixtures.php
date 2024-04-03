<?php

namespace App\DataFixtures;

use DateTime;
use App\Entity\SupportMember;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class SupportMemberFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        // Admin
        $user = new SupportMember();
        $user->setId(1337);
        $user->setFirstName("Admin");
        $user->setLastName("de Baas");
        $user->setEmail("admindebaas@example.com");
        $user->setContributionPerPeriodInEuros(5);
        $manager->persist($user);

        $user2 = new SupportMember();
        $user2->setId(1337);
        $user2->setFirstName("Wow");
        $user2->setLastName("de NietBaas");
        $user2->setEmail("admindebaas@example.com");
        $user2->setContributionPerPeriodInEuros(50);
        $manager->persist($user2);

        $manager->flush();
    }
}
