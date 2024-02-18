<?php

namespace App\Security;

use App\Entity\Member;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class IdOrEmailMemberProvider implements UserProviderInterface {

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager) {
        $this->entityManager = $entityManager;
    }

    public function loadUserByUsername(string $usernameOrEmail): UserInterface {
        $user = $this->entityManager->createQuery('SELECT m FROM App\Entity\Member m WHERE m.id = ?1 OR m.email = ?1')
            ->setParameter(1, $usernameOrEmail)
            ->getOneOrNullResult()
        ;
        if ($user === null)
            throw new UsernameNotFoundException();
        return $user;
    }

    public function refreshUser(UserInterface $user) {
        return $this->loadUserByUsername($user->getUsername());
    }

    public function supportsClass($class) {
        return $class === Member::class;
    }

}
