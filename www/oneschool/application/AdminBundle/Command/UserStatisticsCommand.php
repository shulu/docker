<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 8/5/16
 * Time: 3:27 PM
 */

namespace Lychee\Bundle\AdminBundle\Command;


use Lychee\Bundle\CoreBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UserStatisticsCommand extends ContainerAwareCommand {

    protected function configure() {
        $this->setName('lychee-admin:user-statistics')
            ->setDescription('User statistics');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return null
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        /** @var \PDO $conn */
        $conn = $this->getContainer()->get('doctrine')->getConnection();

        $userCount = $this->fetchTotalUsers($conn);
        $output->writeln('UserCount: ' . $userCount);
        $list = [
            ['Total users', $userCount]
        ];

        $genders = $this->fetchGender($conn);
        $maleCount = $genders['male'];
        $femaleCount = $genders['female'];
        $unknownGender = $userCount - $maleCount - $femaleCount;
        $output->writeln(sprintf("Male: %s, Female: %s", $maleCount, $femaleCount));
        $list[] = ['Gender'];
        $list[] = ['Male', $maleCount, round($maleCount/$userCount*100, 2) . '%'];
        $list[] = ['Female', $femaleCount, round($femaleCount/$userCount*100, 2) . '%'];
        $list[] = ['Unknown', $unknownGender, round($unknownGender/$userCount*100) . '%'];

        $ages = $this->fetchAges($conn);
        $list[] = ['Ages - UserCount'];
        $restUserCount = $userCount;
        foreach ($ages as $row) {
            list($age, $count) = $row;
            $output->writeln(sprintf("Age: %s, Count: %s", $age, $count));
            $list[] = [$age, $count, round($count/$userCount*100, 2) . '%'];
            $restUserCount -= $count;
        }
        $list[] = ['Unknown', $restUserCount, round($restUserCount/$userCount*100, 2) . '%'];
        $filename = 'user-statistics.csv';
        $output->writeln('Start to write csv file(' . $filename . ')');
        $fp = fopen($filename, 'w');
        foreach ($list as $fields) {
            fputcsv($fp, $fields);
        }
        fclose($fp);
        $output->writeln('Completed');

        return null;
    }

    /**
     * @param \PDO $conn
     * @return mixed
     */
    private function fetchTotalUsers($conn) {
        $stmt = $conn->prepare('SELECT COUNT(id) user_count FROM user');
        $stmt->execute();
        $result = $stmt->fetch();
        $userCount = $result['user_count'];

        return $userCount;
    }

    /**
     * @param \PDO $conn
     * @return array
     */
    private function fetchGender($conn) {
        $stmt = $conn->prepare(
            'SELECT gender, COUNT(gender) gender_count FROM user
            WHERE gender IS NOT NULL
            GROUP BY gender'
        );
        $stmt->execute();
        $gender = $stmt->fetchAll();
        $ret = [
            'male' => 0,
            'female' => 0,
        ];
        foreach ($gender as $r) {
            if ($r['gender'] == User::GENDER_MALE) {
                $ret['male'] = $r['gender_count'];
            } elseif ($r['gender'] == User::GENDER_FEMALE) {
                $ret['female'] = $r['gender_count'];
            }
        }

        return $ret;
    }

    /**
     * @param \PDO $conn
     * @return array
     */
    private function fetchAges($conn) {
        $date = getdate();
        $year = $date['year'];
        for ($i = 0; $i <= 100; $i++) {
            $queryYear = $year - $i;
            $stmt = $conn->prepare(
                'SELECT COUNT(u.id) user_count FROM user u
                LEFT OUTER JOIN user_profile p ON p.user_id=u.id
                WHERE p.birthday >= :start AND p.birthday <= :end'
            );
            $stmt->bindValue(':start', sprintf('%s-01-01', $queryYear));
            $stmt->bindValue(':end', sprintf('%s-12-31', $queryYear));
            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            yield [$i, $result['user_count']];
        }
    }
}