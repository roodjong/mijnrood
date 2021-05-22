<?php

namespace App\DataFixtures;

use App\Entity\Member;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class MemberFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $member = new Member();
        $member->setId(1337);
        $member->setFirstName("VoornaamAdmin");
        $member->setLastName("AchternaamAdmin");
        $member->setIsAdmin(true);
        $member->setContributionPerPeriodInEuros(5);
        $member->setPasswordHash(password_hash("admin", PASSWORD_DEFAULT));
        $manager->persist($member);

        $manager->flush();
    }
}
