<?php

namespace App\Repository;

use App\Entity\Playlist;
use App\Entity\User;
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

    public function hardDeleteBySessionCodeIfNotExported(string $sessionCode): void
    {
        $qb = $this->createQueryBuilder('p');
        $qb->delete()
            ->where('p.sessionCode = :sessionCode')
            ->andWhere('p.exportedPlaylistId IS NULL')
            ->andWhere('p.exportedPlaylistUrl IS NULL')
            ->setParameter('sessionCode', $sessionCode)
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * @return Playlist[]
     */
    public function findExportedPlaylistsByUser(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.user = :user')
            ->andWhere('p.exportedPlaylistId IS NOT NULL')
            ->andWhere('p.exportedPlaylistUrl IS NOT NULL')
            ->orderBy('p.updatedAt', 'DESC')
            ->setParameter('user', (string) $user->getId()->toBinary())
            ->getQuery()
            ->getResult()
        ;
    }
}
