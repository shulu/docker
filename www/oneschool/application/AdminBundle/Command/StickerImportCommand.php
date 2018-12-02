<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 3/19/15
 * Time: 2:10 PM
 */

namespace Lychee\Bundle\AdminBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Lychee\Module\ContentManagement\Entity\Sticker;
use Lychee\Module\ContentManagement\Entity\StickerVersion;

class StickerImportCommand extends ContainerAwareCommand {

    protected function configure() {
        $this->setName('lychee:sticker:import')
            ->setDescription('Import Stickers to Database.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $jsonFile = $this->getContainer()->get('kernel')->getRootDir() .
            '/../../src/Lychee/Bundle/ApiBundle/Resources/pasters/paster_1.json';
        $jsonObj = json_decode(file_get_contents($jsonFile));

        $stickerVersion = $jsonObj->version;
        $stickers = $jsonObj->packages;
        /**
         * @var \Doctrine\ORM\EntityManager $em
         */
        $em = $this->getContainer()->get('Doctrine')->getManager();
        $stickerRepo = $em->getRepository(Sticker::class);
        foreach ($stickers as $row) {
            $sticker = $stickerRepo->find($row->package_id);
            if (null === $sticker) {
                // Create
                $em->getConnection()->insert('sticker', [
                    'id' => $row->package_id,
                    'name' => $row->name,
                    'is_new' => isset($row->is_new)? $row->is_new : 0,
                    'thumbnail_url' => $row->thumbnail_url,
                    'url' => $row->url,
                ]);
            } else {
                $sticker->name = $row->name;
                $sticker->isNew = isset($row->is_new)? $row->is_new : 0;
                $sticker->thumbnailUrl = $row->thumbnail_url;
                $sticker->url = $row->url;
            }
            $em->flush();
        }
        $stickerVersionRepo = $em->getRepository(StickerVersion::class);
        $versions = $stickerVersionRepo->findAll();
        if (empty($versions)) {
            $stickerVersionEntity = new StickerVersion();
            $stickerVersionEntity->version = $stickerVersion;
            $em->persist($stickerVersionEntity);
        } else {
            /**
             * @var \Lychee\Module\ContentManagement\Entity\StickerVersion $curVersion
             */
            $curVersion = $versions[0];
            $curVersion->version = $stickerVersion;
        }
        $em->flush();
        $output->writeln('All Done.');
    }

}