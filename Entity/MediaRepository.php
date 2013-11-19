<?php 
namespace Majes\MediaBundle\Entity;

use Doctrine\ORM\EntityRepository;

class MediaRepository extends EntityRepository
{
    public function findForAdmin($types = null, $folders = null, $page = 1, $limit = 20)
    {

    	$offset = ($page - 1) * $limit;
    	$limit++;
    	
    	$q = $this
            ->createQueryBuilder('m')
            ->setFirstResult( $offset )
       		->setMaxResults( $limit );

       	if(!is_null($types)){

            $q = $q->where('m.type IN (:types)')
            	->setParameter('types', $types);
       	}

       	if(!is_null($folders)){

            $q = $q->andWhere('m.folder IN (:folders)')
            	->setParameter('folders', $folders);
       	}

        $q = $q->getQuery();

		return $medias = $q->getResult();


    }

    public function listFolders()
    {


      $q = $this
            ->createQueryBuilder('m')
            ->groupby('m.folder');

        $q = $q->getQuery();

      return $folders = $q->getResult();


    }
}