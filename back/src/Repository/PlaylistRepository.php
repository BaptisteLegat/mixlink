<?php

namespace App\Repository;

use App\Entity\Playlist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Playlist>
 *
 * @method Playlist|null find($id, $lockMode = null, $lockVersion = null)
 * @method Playlist|null findOneBy(array $criteria, array $orderBy = null)
 * @method Playlist[]    findAll()
 * @method Playlist[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PlaylistRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Playlist::class);
    }

    /**
     * @SuppressWarnings("PHPMD.BooleanArgumentFlag")
     */
    public function save(Playlist $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @SuppressWarnings("PHPMD.BooleanArgumentFlag")
     */
    public function remove(Playlist $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function hardDeleteBySessionCode(string $sessionCode): void
    {
        $qb = $this->createQueryBuilder('p');
        $qb->delete()
            ->where('p.sessionCode = :sessionCode')
            ->andWhere($qb->expr()->eq('SIZE(p.songs)', 0))
            ->setParameter('sessionCode', $sessionCode)
            ->getQuery()
            ->execute()
        ;
    }
}
