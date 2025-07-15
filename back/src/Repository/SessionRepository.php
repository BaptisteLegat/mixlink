<?php

namespace App\Repository;

use App\Entity\Session;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Session>
 */
class SessionRepository extends ServiceEntityRepository
{
    private const CHARACTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    private const CODE_LENGTH = 8;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Session::class);
    }

    public function save(Session $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Session $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOldInactiveSessions(DateTimeImmutable $cutoffDate): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.isActive = :isActive')
            ->andWhere('s.endedAt IS NOT NULL')
            ->andWhere('s.endedAt < :cutoffDate')
            ->setParameter('isActive', false)
            ->setParameter('cutoffDate', $cutoffDate)
            ->getQuery()
            ->getResult()
        ;
    }

    public function generateUniqueCode(): string
    {
        do {
            $code = $this->generateCode();
        } while (null !== $this->findOneBy(['code' => $code]));

        return $code;
    }

    private function generateCode(): string
    {
        $code = '';

        for ($i = 0; $i < self::CODE_LENGTH; ++$i) {
            $code .= self::CHARACTERS[random_int(0, strlen(self::CHARACTERS) - 1)];
        }

        return $code;
    }
}
