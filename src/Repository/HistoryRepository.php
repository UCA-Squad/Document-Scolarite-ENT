<?php


namespace App\Repository;


use App\Entity\History;
use App\Entity\ImportedData;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

class HistoryRepository extends ServiceEntityRepository
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, History::class);
	}

	public function findRNHistoriesForUser(string $username, string $order = "DESC"): array
	{
		return $this->createQueryBuilder('h')
			->innerJoin('h.importedData', 'd', Join::WITH, 'h.importedData = d.id')
			->Where('d.username = :username')
			->andwhere('d.semestre IS NOT NULL')
			->andWhere('d.session IS NOT NULL')
			->andWhere('d.libelle_form IS NOT NULL')
			->setParameter('username', $username)
			->orderBy('h.date', $order)
			->getQuery()
			->getResult();
	}

	public function findAttestHistoriesForUser(string $username, string $order = "DESC"): array
	{
		return $this->createQueryBuilder('h')
			->innerJoin('h.importedData', 'd', Join::WITH, 'h.importedData = d.id')
			->Where('d.username = :username')
			->andwhere('d.semestre IS NULL')
			->andWhere('d.session IS NULL')
			->andWhere('d.libelle_form IS NULL')
			->setParameter('username', $username)
			->orderBy('h.date', $order)
			->getQuery()
			->getResult();
	}

}