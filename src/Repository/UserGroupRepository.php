<?php

namespace App\Repository;

use App\Entity\ImportedData;
use App\Entity\UserGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @extends ServiceEntityRepository<UserGroup>
 */
class UserGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private Security $security)
    {
        parent::__construct($registry, UserGroup::class);
    }

    //    /**
    //     * @return UserGroup[] Returns an array of UserGroup objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?UserGroup
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    public function delete(UserGroup $userGrp): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($userGrp);
        $entityManager->flush();
    }

    public function getUsernamesByResponsable(string $respUsername): array
    {
        $userGroups = $this->findBy(['username' => $respUsername, 'responsable' => true]);

        $groups = array_map(function ($userGroup) {
            return $userGroup->getGroupe();
        }, $userGroups);

        // get all username from user of group
        $usernames = [];
        foreach ($groups as $group) {
            $userGroups = $group->getUserGroups();
            foreach ($userGroups as $userGroup) {
                if ($userGroup->isUser())
                    $usernames[] = $userGroup->getUsername();
            }
        }

        $usernames[] = $respUsername;

        return $usernames;
    }

    /**
     * @param ImportedData $import
     * @param string $username
     * @return bool
     * Indique si l'utilisateur $username a le droit de consulter l'import $import
     */
    public function hasRightOn(ImportedData $import, string $username): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        $usernames = $this->getUsernamesByResponsable($username);

        return in_array($import->getUsername(), $usernames);
    }
}
