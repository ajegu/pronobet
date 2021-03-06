<?php

namespace AppBundle\Repository;

use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * UserRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserRepository extends \Doctrine\ORM\EntityRepository
{
    public function getMembers($firstResult = 0, $maxResult = 10, $search = '', $status = '')
    {
        $qd = $this->createQueryBuilder('user');
        $qd
            ->where('user.role = ?1 OR user.role = ?2')
            ->setFirstResult($firstResult)
            ->setMaxResults($maxResult)
            ->setParameter('1', 'ROLE_MEMBER')
            ->setParameter('2', 'ROLE_SUBSCRIBER');

        if ($search !== '') {
            $qd->andWhere('user.nickname LIKE :search OR user.email LIKE :search')
                ->setParameter('search', "%".$search."%");
        }

        if ($status !== '') {
            $qd->andWhere('user.role = :role')
                ->setParameter('role', $status);
        }

        $paginator = new Paginator($qd);

        return $paginator;
    }

    public function getTipsters($firstResult = 0, $maxResult = 10, $search = '')
    {
        $qd = $this->createQueryBuilder('user');
        $qd
            ->innerJoin('user.tipster', 't')
            ->where('user.role = ?1')
            ->setFirstResult($firstResult)
            ->setMaxResults($maxResult)
            ->setParameter('1', 'ROLE_TIPSTER');

        if ($search !== '') {
            $qd->andWhere('user.nickname LIKE :search OR user.email LIKE :search')
                ->setParameter('search', "%".$search."%");
        }

        $paginator = new Paginator($qd);

        return $paginator;
    }
}
