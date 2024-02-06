<?php


namespace App\Repository;


use App\Entity\ImportedData;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

class ImportedDataRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ImportedData::class);
    }

    public function findLastDataByMode(int $mode, string $username)
    {
        return $mode == ImportedData::RN ? $this->findLastRnData($username) : $this->findLastAttestData($username);
    }

    public function findAllRns(string $username = null)
    {
        $query = $this->createQueryBuilder('i')
            ->where('i.semestre != :semestre')
            ->andWhere('i.session != :session')
            ->setParameter('semestre', '')
            ->setParameter('session', '');

        if (isset($username)) {
            $query->andWhere('i.username = :username')
                ->setParameter('username', $username);
        }

        return $query->getQuery()
            ->getResult();
    }

    public function findAllAttests(string $username = null)
    {
        $query = $this->createQueryBuilder('i')
            ->where('i.semestre = :semestre')
            ->andWhere('i.session = :session')
            ->setParameter('semestre', '')
            ->setParameter('session', '');

        if (isset($username)) {
            $query->andWhere('i.username = :username')
                ->setParameter('username', $username);
        }

        return $query->getQuery()
            ->getResult();
    }

    /**
     * Return the last attestation ImportedData for the user 'username'.
     * @param string $username
     * @return ImportedData|null
     * @throws NonUniqueResultException
     */
    public function findLastAttestData(string $username): ?ImportedData
    {
        return $this->createQueryBuilder('d')
            ->where('d.semestre is null')
            ->andWhere('d.session is null')
            ->andWhere('d.libelle_form is null')
            ->andWhere('d.username = :username')
            ->innerJoin('d.history', 'h', Join::WITH, 'd.id = h.importedData')
            ->setParameter('username', $username)
            ->orderBy('h.date', "DESC")
            ->setFirstResult(0)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Return the last releve ImportedData for the user 'username'.
     * @param string $username
     * @return ImportedData|null
     * @throws NonUniqueResultException
     */
    public function findLastRnData(string $username): ?ImportedData
    {
        return $this->createQueryBuilder('d')
            ->where('d.semestre is not null')
            ->andWhere('d.session is not null')
            ->andWhere('d.libelle_form is not null')
            ->andWhere('d.username = :username')
            ->innerJoin('d.history', 'h', Join::WITH, 'd.id = h.importedData')
            ->setParameter('username', $username)
            ->orderBy('h.date', "DESC")
            ->setFirstResult(0)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findRnFromUsername(string $username): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.semestre IS NOT NULL')
            ->andWhere('i.session IS NOT NULL')
            ->andWhere('i.libelle_form IS NOT NULL')
            ->andWhere('i.username = :username')
            ->innerJoin('i.history', 'h', Join::WITH, 'i.id = h.importedData')
            ->orderBy('h.date', "DESC")
            ->setParameter('username', $username)
            ->getQuery()
            ->getResult();
    }

    public function findAttestFromUsername(string $username): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.semestre IS NULL')
            ->andWhere('i.session IS NULL')
            ->andWhere('i.libelle_form IS NULL')
            ->andWhere('i.username = :username')
            ->innerJoin('i.history', 'h', Join::WITH, 'i.id = h.importedData')
            ->orderBy('h.date', "DESC")
            ->setParameter('username', $username)
            ->getQuery()
            ->getResult();
    }

    public function findRn(ImportedData $data, string $username, bool $admin = false): ?ImportedData
    {
        $query = $this->createQueryBuilder('i')
            ->where('i.semestre = :semestre')
            ->andWhere('i.session = :session')
            ->andWhere('i.libelle_form is not null')
            ->andWhere('i.etu_filename = :etu')
            ->andWhere('i.year = :year')
            ->setParameter('semestre', $data->getSemestre())
            ->setParameter('session', $data->getSession())
            ->setParameter('etu', $data->getEtu()->getClientOriginalName())
            ->setParameter('year', $data->getYear() . '-' . (substr($data->getYear(), 2, 2) + 1));

        if (!$admin) {
            $query->andWhere('i.username = :username')
                ->setParameter('username', $username);
        }

        return $query
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();    // Retourne null si plusieurs entrées // a CHECK
    }

    public function findAttest(ImportedData $data, string $username, bool $admin = false): ?ImportedData
    {
        $query = $this->createQueryBuilder('i')
            ->where('i.semestre IS NULL')
            ->andWhere('i.session IS NULL')
            ->andWhere('i.libelle_form IS NULL')
            ->andWhere('i.etu_filename = :etu')
            ->andWhere('i.year = :year')
            ->setParameter('etu', $data->getEtu()->getClientOriginalName())
            ->setParameter('year', $data->getYear() . '-' . (substr($data->getYear(), 2, 2) + 1));

        if (!$admin) {
            $query->andWhere('i.username = :username')
                ->setParameter('username', $username);
        }

        return $query->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}