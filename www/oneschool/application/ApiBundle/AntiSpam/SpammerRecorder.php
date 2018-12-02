<?php
namespace Lychee\Bundle\ApiBundle\AntiSpam;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Lychee\Bundle\ApiBundle\Entity\SpammerRecord;

class SpammerRecorder {
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * SpammerRecorder constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct($registry) {
        $this->em = $registry->getManager();
    }

    /**
     * @param int $userId
     */
    public function record($userId) {
        $sql = 'INSERT INTO spammer_records(spammer_id, `time`) VALUES(?, ?)'
            .' ON DUPLICATE KEY UPDATE `time` = `time`';
        $this->em->getConnection()->executeUpdate($sql, array($userId, time()),
            array(\PDO::PARAM_INT, \PDO::PARAM_INT));
    }

    /**
     * @param int $cursor
     * @param int $count
     * @param int $nextCursor
     *
     * @return SpammerRecord[]
     */
    public function getSpammers($cursor, $count, &$nextCursor) {
        if ($count == 0) {
            $nextCursor = $cursor;
            return array();
        }

        $query = $this->em->createQuery('SELECT r FROM '.SpammerRecord::class.' r WHERE r.id > :cursor');
        $query->setParameter('cursor', $cursor);
        $query->setMaxResults($count);
        $result = $query->getResult();
        if (count($result) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $result[count($result) - 1]->id;
        }

        return $result;
    }

    public function isUserSpammer($userId) {

    	$spammer = $this->em->getRepository('Lychee\Bundle\ApiBundle\Entity\SpammerRecord')->findOneBy(
    		array(
    			'spammerId' => $userId
		    )
	    );
	    return ($spammer != null);
    }

}