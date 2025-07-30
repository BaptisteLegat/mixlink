<?php

namespace App\Repository;

use App\Entity\Song;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Song>
 *
 * @method Song|null find($id, $lockMode = null, $lockVersion = null)
 * @method Song|null findOneBy(array $criteria, array $orderBy = null)
 * @method Song[]    findAll()
 * @method Song[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SongRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Song::class);
    }

    /**
     * @SuppressWarnings("PHPMD.BooleanArgumentFlag")
     */
    public function save(Song $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @SuppressWarnings("PHPMD.BooleanArgumentFlag")
     */
    public function remove(Song $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function hardDeleteOrphanedSongs(): void
    {
        $qb = $this->createQueryBuilder('s');
        $qb->delete()
            ->where('SIZE(s.playlists) = 0')
            ->andWhere('s.deletedAt IS NULL')
            ->getQuery()
            ->execute()
        ;
    }

    public function hardDeleteBySpotifyId(string $spotifyId): void
    {
        $qb = $this->createQueryBuilder('s');
        $qb->delete()
            ->where('s.spotifyId = :spotifyId')
            ->andWhere('s.deletedAt IS NULL')
            ->setParameter('spotifyId', $spotifyId)
            ->getQuery()
            ->execute()
        ;
    }
}
