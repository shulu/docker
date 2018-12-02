<?php
namespace Lychee\Module\Voting;

use Doctrine\ORM\EntityManagerInterface;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Module\Voting\Entity\VotingVoter;
use Lychee\Module\Voting\Exception\InvalidOptionException;
use Lychee\Module\Voting\Exception\InvalidVotingException;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Lychee\Module\Voting\Entity\VotingOption;
use Lychee\Module\Voting\Entity\Voting;

class VotingService {

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * VotingService constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct($registry) {
        $this->em = $registry->getManager();
    }

    /**
     * @param int $postId
     * @param string $title
     * @param string $description
     * @param VotingOption[] $options
     *
     * @return Voting
     */
    public function create($postId, $title, $description, $options) {
        $voting = new Voting($postId, $title, $description, $options);
        $this->em->persist($voting);
        $this->em->flush($voting);

        return $voting;
    }

    /**
     * @param int $id
     *
     * @return Voting|null
     */
    public function get($id) {
        return $this->em->find(Voting::class, $id);
    }

    /**
     * @param int[] $ids
     *
     * @return Voting[]
     */
    public function multiGet($ids) {
        if (count($ids) == 0) {
            return array();
        }
        /** @var Voting[] $schedules */
        $votings = $this->em->getRepository(Voting::class)->findBy(array('id' => $ids));
        $votingsByIds = array();
        foreach ($votings as $voting) {
            $votingsByIds[$voting->id] = $voting;
        }
        $result = array();
        foreach ($ids as $id) {
            $result[$id] = $votingsByIds[$id];
        }

        return $result;
    }

    /**
     * @param int $votingId
     * @param int $countPerOption
     *
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getLatestVotersPerOption($votingId, $countPerOption) {
        $voting = $this->get($votingId);
        if ($voting == null) {
            return array();
        }
        $sqls = array();
        $optionCount = count($voting->getOptions());
        for ($i = 1; $i <= $optionCount; ++$i) {
            $sqls[] = '(SELECT `option`, voter_id FROM voting_voters WHERE voting_id = '.$votingId
                .' AND `option` = '.$i.' ORDER BY time DESC, voter_id DESC LIMIT '.$countPerOption.')';
        }
        $sql = implode(' UNION ALL ', $sqls);
        $stat = $this->em->getConnection()->executeQuery($sql);
        $rows = $stat->fetchAll(\PDO::FETCH_ASSOC);
        return ArrayUtility::columnsGroupBy($rows, 'voter_id', 'option');
    }

    /**
     * @param int $votingId
     * @param int $optionId
     * @param string $cursor
     * @param int $count
     * @param string $nextCursor
     *
     * @return int[]
     */
    public function getOptionVoters($votingId, $optionId, $cursor, $count, &$nextCursor = null) {
        if ($count <= 0) {
            $nextCursor = $cursor;
            return array();
        }
        @list($position, $voterId) = explode(',', $cursor);
        $position = intval($position);
        if ($position == 0) {
            $position = PHP_INT_MAX;
        }
        $voterId = intval($voterId);

        $query = $this->em->createQuery('SELECT v FROM '.VotingVoter::class
            .' v WHERE v.votingId = :voting AND v.option = :option AND ((v.time = :time AND v.voterId < :voter) OR v.time < :time)'
            .' ORDER BY v.time DESC, v.voterId DESC');
        $query->setParameters(array('voting' => $votingId, 'option' => $optionId,
                                    'time' => $position, 'voter' => $voterId));
        $query->setMaxResults($count);
        $voters = $query->getResult();

        if (count($voters) < $count) {
            $nextCursor = 0;
        } else {
            /** @var VotingVoter $last */
            $last = $voters[count($voters) - 1];
            $nextCursor = $last->time . ',' . $last->voterId;
        }

        return ArrayUtility::columns($voters, 'voterId');
    }

    /**
     * @param int $voterId
     * @param int[] $votingIds
     *
     * @return VoteResolver
     * @throws \Doctrine\DBAL\DBALException
     */
    public function buildVoteResolver($voterId, $votingIds) {
        if (count($votingIds) == 0) {
            return new VoteResolver(array());
        }

        $sql = 'SELECT voting_id, `option` FROM voting_voters WHERE voting_id IN ('
            .implode(',', $votingIds).') AND voter_id = ?';
        $stat = $this->em->getConnection()->executeQuery($sql, array($voterId), array(\PDO::PARAM_INT));
        $rows = $stat->fetchAll(\PDO::FETCH_ASSOC);
        $optionsByVotings= ArrayUtility::columns($rows, 'option', 'voting_id');
        return new VoteResolver($optionsByVotings);
    }

    /**
     * @param int $voterId
     * @param int $votingId
     * @param int $optionId
     *
     * @throws InvalidOptionException
     * @throws InvalidVotingException
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    public function vote($voterId, $votingId, $optionId) {
        if ($optionId < 1 || $optionId > 10) {
            throw new InvalidOptionException();
        }

        $conn = $this->em->getConnection();

        $selectSql = 'SELECT opt'.$optionId.' opt FROM voting WHERE id = ?';
        $stat = $conn->executeQuery($selectSql, array($votingId), array(\PDO::PARAM_INT));
        $r = $stat->fetch(\PDO::FETCH_ASSOC);
        if ($r == false) {
            throw new InvalidVotingException();
        }
        if ($r['opt'] == null) {
            throw new InvalidOptionException();
        }

        try {
            $conn->beginTransaction();
            $time = time();
            $insertSql = 'INSERT INTO voting_voters(voting_id, voter_id, `option`, `time`) VALUES(?, ?, ?, ?)'
                .' ON DUPLICATE KEY UPDATE `time` = `time`';
            $affactedRow = $conn->executeUpdate($insertSql, array($votingId, $voterId, $optionId, $time),
                array(\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT));
            if ($affactedRow == 1) {
                $optionKey = 'opt'.$optionId.'_count';
                $updateSql = 'UPDATE voting SET vote_count = vote_count + 1, '.$optionKey.' = '
                    .$optionKey.' + 1 WHERE id = ?';
                $conn->executeUpdate($updateSql, array($votingId), array(\PDO::PARAM_INT));
            }
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

}