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
        $admin->setEmail("admindebaas@localhost");
        $admin->setIsAdmin(true);
        $admin->setContributionPerPeriodInEuros(5);
        $admin->setPasswordHash(password_hash("admin", PASSWORD_DEFAULT));
        $manager->persist($admin);

        // Add Division (Groep) with contact member and new member
        $division = new Division();
        $division->setName("Noorderhaaks");
        $division->setPostCode("1234AB");
        
        $contact = new Member();
        $contact->setId(1338);
        $contact->setFirstName("Jan");
        $contact->setLastName("Jansen");
        $contact->setEmail("janjansen@localhost");
        $contact->setContributionPerPeriodInEuros(5);
        $contact->setPasswordHash(password_hash("contact", PASSWORD_DEFAULT));
        $contact->setDivision($division);
        $contact->setRegistrationTime(new DateTime('2021-06-01T15:21:01.012345Z'));
        $division->setContact($contact);

        $newMember = new Member();
        $newMember->setId(1339);
        $newMember->setFirstName("Henk");
        $newMember->setLastName("de Vries");
        $newMember->setEmail("henkdevries@localhost");
        $newMember->setContributionPerPeriodInEuros(5);
        $newMember->setPasswordHash(password_hash("new_member", PASSWORD_DEFAULT));
        $newMember->setDivision($division);
        $newMember->setRegistrationTime(new DateTime('2021-01-01T15:03:01.012345Z'));

        $manager->persist($contact);
        $manager->persist($division);
        $manager->persist($newMember);

        $manager->flush();
    }
}
