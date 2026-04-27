<?php

namespace App\Repository;

use App\Entity\Filiere;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FiliereRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Filiere::class);
    }

    // Trouve toutes les filières triées par domaine puis par nom
    public function findAllOrderedByDomain(): array
    {
        return $this->createQueryBuilder('f')
            ->orderBy('f.domain', 'ASC')
            ->addOrderBy('f.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // Trouve les filières d'un domaine spécifique
    public function findByDomain(string $domain): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.domain = :domain')
            ->setParameter('domain', $domain)
            ->orderBy('f.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // Recherche une filière par nom (recherche partielle)
    public function findByName(string $name): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.name LIKE :name')
            ->setParameter('name', '%' . $name . '%')
            ->orderBy('f.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
