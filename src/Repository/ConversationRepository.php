<?php

namespace App\Repository;

use App\Entity\Conversation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conversation::class);
    }

    // Trouve les conversations d'une filière triées par date
    public function findByFiliereOrderedByDate(int $filiereId): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.filiere = :filiereId')
            ->setParameter('filiereId', $filiereId)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // Trouve les conversations d'un utilisateur
    public function findByParticipant(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.participants', 'u')
            ->andWhere('u = :user')
            ->setParameter('user', $user)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // Trouve les conversations créées par un utilisateur
    public function findByCreator(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.creator = :user')
            ->setParameter('user', $user)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // Recherche par titre
    public function findByTitle(string $title): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.title LIKE :title')
            ->setParameter('title', '%' . $title . '%')
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
