<?php

namespace App\Repository;

use App\Entity\GroupSession;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<GroupSession> */
class GroupSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GroupSession::class);
    }

    public function findByCode(string $code): ?GroupSession
    {
        return $this->findOneBy(['code' => strtoupper($code)]);
    }
}
