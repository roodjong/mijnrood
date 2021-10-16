<?php
namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use App\Entity\SupportMember;

class SupportMemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SupportMember::class);
    }

    public function sumByContributionPerMonth(): float
    {
        $entityManager = $this->getEntityManager();

        $query = $entityManager->createQueryBuilder()
                               ->select('SUM(sm.contributionPerPeriodInCents)')
                               ->from('App\Entity\SupportMember', 'sm')
                               ->getQuery();
        return floatval($query->getSingleScalarResult());
    }
}

