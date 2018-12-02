<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 6/24/15
 * Time: 4:27 PM
 */

namespace Lychee\Bundle\AdminBundle\Command;


use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Lychee\Module\Topic\Exception\RunOutOfCreatingQuotaException;
use Lychee\Module\Topic\Exception\TopicAlreadyExistException;
use Lychee\Module\Topic\TopicParameter;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Lychee\Module\Topic\Entity\Topic;

class TopicGeneratorCommand extends ContainerAwareCommand {
    use ModuleAwareTrait;

    protected function configure() {
        $this->setName('lychee-admin:topic:generator')
            ->setDescription('Create Topic(s)')
            ->addArgument('file', InputArgument::REQUIRED, 'CSV file.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $file = $input->getArgument('file');
        if (($handle = fopen($file, 'r')) !== false) {
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                $creator = isset($data[1])? $data[1] : 31721;
                $topicName = $data[0];
                if ($topicName) {
                    $originQuota = $this->topic()->getUserCreatingQuota($creator);
                    if ($originQuota < 100) {
                        $this->topic()->increaseUserCreatingQuota($creator, 100);
                    }
                    /**
                     * @var \Doctrine\ORM\EntityManager $em
                     */
                    $em = $this->getContainer()->get('Doctrine')->getManager();
                    $topicRepo = $em->getRepository(Topic::class);
                    $topic = $topicRepo->findOneBy([
                        'title' => $topicName,
                    ]);
                    if (null === $topic) {
                        try {
                            $p = new TopicParameter();
                            $p->creatorId = $creator;
                            $p->title = $topicName;
                            $this->topic()->create($p);
                            $output->writeln(sprintf("Topic [%s] Created.", $topicName));
                        } catch (TopicAlreadyExistException $e) {
                            $output->writeln(sprintf("Topic [%s] has already exist.", $topicName));
                        } catch (RunOutOfCreatingQuotaException $e) {
                            $output->writeln(sprintf("User [%s] run out of creating quota.", $creator));
                            break;
                        } catch (\Exception $e) {
                            $output->writeln($e->getMessage());
                            break;
                        }
                    }
                }
            }
        }
    }
}