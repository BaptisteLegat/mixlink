<?php

namespace App\Repository;

use App\Entity\Session;
use App\Entity\SessionParticipant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SessionParticipant>
 */
class SessionParticipantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SessionParticipant::class);
    }

    public function save(SessionParticipant $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(SessionParticipant $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return SessionParticipant[]
     */
    public function findActiveBySession(Session $session): array
    {
        return $this->createQueryBuilder('sp')
            ->andWhere('sp.session = :session')
            ->andWhere('sp.isActive = :isActive')
            ->setParameter('session', $session->getId()->toBinary())
            ->setParameter('isActive', true)
            ->orderBy('sp.createdAt', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findBySessionAndPseudo(Session $session, string $pseudo): ?SessionParticipant
    {
        return $this->createQueryBuilder('sp')
            ->andWhere('sp.session = :session')
            ->andWhere('sp.pseudo = :pseudo')
            ->andWhere('sp.isActive = true')
            ->setParameter('session', $session->getId()->toBinary())
            ->setParameter('pseudo', $pseudo)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function countActiveBySession(Session $session): int
    {
        return $this->createQueryBuilder('sp')
            ->select('COUNT(sp.id)')
            ->andWhere('sp.session = :session')
            ->andWhere('sp.isActive = true')
            ->setParameter('session', $session->getId()->toBinary())
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
