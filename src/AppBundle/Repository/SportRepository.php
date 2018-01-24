<?php

namespace AppBundle\Repository;

use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * SportRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class SportRepository extends \Doctrine\ORM\EntityRepository
{
    public function getSports($firstResult = 0, $maxResult = 10, $search = '', $state = '')
    {
        $qd = $this->createQueryBuilder('sport');

        if ($maxResult > 0) {
            $qd
                ->setFirstResult($firstResult)
                ->setMaxResults($maxResult)
            ;
        }

        if ($search !== '') {
            $qd->andWhere('sport.name LIKE :search')
                ->setParameter('search', "%".$search."%");
        }

        if ($state !== '') {
            $qd->andWhere('sport.visible = :state')
                ->setParameter('state', $state);
        }

        $qd->orderBy('sport.name', 'asc');

        $paginator = new Paginator($qd);

        return $paginator;
    }
}