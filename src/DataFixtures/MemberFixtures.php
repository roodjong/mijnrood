<?php

namespace App\DataFixtures;

use DateTime;
use App\Entity\Member;
use App\Entity\Division;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class MemberFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        // Admin
        $admin = new Member();
        $admin->setId(1337);
        $admin->setFirstName("Admin");
        $admin->setLastName("de Baas");
        $admin->setEmail("admindebaas@example.com");
        $admin->setIsAdmin(true);
        $admin->setContributionPerPeriodInEuros(5);
        $admin->setPasswordHash(password_hash("admin", PASSWORD_DEFAULT));
        $admin->setComments("Dit is de Baas");
        $manager->persist($admin);

        // Add Division (Groep) with contact member and new member
        $divisionNooderhaaks = new Division();
        $divisionNooderhaaks->setName("Noorderhaaks");
        $divisionNooderhaaks->setPostCode("1234AB");

        $contactNooderhaaks = new Member();
        $contactNooderhaaks->setId(1338);
        $contactNooderhaaks->setFirstName("Jan");
        $contactNooderhaaks->setLastName("Jansen");
        $contactNooderhaaks->setEmail("janjansen@example.com");
        $contactNooderhaaks->setContributionPerPeriodInEuros(5);
        $contactNooderhaaks->setPasswordHash(password_hash("contact", PASSWORD_DEFAULT));
        $contactNooderhaaks->setDivision($divisionNooderhaaks);
        $contactNooderhaaks->setRegistrationTime(new DateTime('2021-06-01T15:21:01.012345Z'));
        $divisionNooderhaaks->addContact($contactNooderhaaks);

        // Add Division (Groep) with another contact member and new member
        $divisionAchterhoek = new Division();
        $divisionAchterhoek->setName("Achterhoek");
        $divisionAchterhoek->setPostCode("4321BA");

        $contactAchterhoek = new Member();
        $contactAchterhoek->setId(8673);
        $contactAchterhoek->setFirstName("Gert");
        $contactAchterhoek->setLastName("Bakker");
        $contactAchterhoek->setEmail("gertbakker@example.com");
        $contactAchterhoek->setContributionPerPeriodInEuros(5);
        $contactAchterhoek->setPasswordHash(password_hash("gert", PASSWORD_DEFAULT));
        $contactAchterhoek->setDivision($divisionAchterhoek);
        $contactAchterhoek->setRegistrationTime(new DateTime('2021-06-01T15:21:01.012345Z'));
        $divisionAchterhoek->addContact($contactAchterhoek);

        // Add regular member of Nooderhaaks
        $newMember = new Member();
        $newMember->setId(1339);
        $newMember->setFirstName("Henk");
        $newMember->setLastName("de Vries");
        $newMember->setEmail("henkdevries@example.com");
        $newMember->setContributionPerPeriodInEuros(5);
        $newMember->setPasswordHash(password_hash("new_member", PASSWORD_DEFAULT));
        $newMember->setDivision($divisionNooderhaaks);
        $newMember->setRegistrationTime(new DateTime('2021-01-01T15:03:01.012345Z'));

        $manager->persist($contactAchterhoek);
        $manager->persist($contactNooderhaaks);
        $manager->persist($divisionNooderhaaks);
        $manager->persist($divisionAchterhoek);
        $manager->persist($newMember);

        $manager->flush();
    }
}
