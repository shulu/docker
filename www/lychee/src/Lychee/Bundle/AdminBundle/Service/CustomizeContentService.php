<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 6/21/16
 * Time: 4:06 PM
 */

namespace Lychee\Bundle\AdminBundle\Service;


use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Lychee\Bundle\AdminBundle\Entity\CustomizeContent;
use Lychee\Component\Foundation\ArrayUtility;

class CustomizeContentService {

    /**
     * @var Registry $doctrine
     */
    private $doctrine;

    public function __construct(Registry $doctrine) {
        $this->doctrine = $doctrine;
    }

    public function add($type, $targetId) {
        /** @var EntityManager $em */
        $em = $this->doctrine->getManager();

        $topic = $em->getRepository(CustomizeContent::class)->findOneBy([
            'type' => $type,
            'targetId' => $targetId,
        ]);
        if ($topic) {
            throw new DuplicateCustomizeContentException($type);
        }

        $customize = new CustomizeContent();
        $customize->type = $type;
        $customize->targetId = $targetId;
        $em->persist($customize);
        $em->flush();
    }
    
    public function delete($type, $targetId) {
        $em = $this->doctrine->getManager();
        $result = $em->getRepository(CustomizeContent::class)->findOneBy([
            'type' => $type,
            'targetId' => $targetId
        ]);
        if ($result) {
            $em->remove($result);
            $em->flush();
        }
    }
    
    public function deleteById($id) {
        $em = $this->doctrine->getManager();
        $customizeContent = $em->getRepository(CustomizeContent::class)->find($id);
        if ($customizeContent) {
            $em->remove($customizeContent);
            $em->flush();
        }
    }
    
    public function fetch($type, $page = 1, $count = 20) {
        $offset = ($page - 1) * $count;
        $em = $this->doctrine->getManager();

        return $em->getRepository(CustomizeContent::class)
            ->findBy([
                'type' => $type,
            ], ['id' => 'DESC'], $count, $offset);
    }

    public function customizeContentCount($type) {
        /** @var \PDO $conn */
        $conn = $this->doctrine->getConnection();
        $stmt = $conn->prepare(
            'SELECT COUNT(id)
            FROM admin_customize_content
            WHERE type=:type'
        );
        $stmt->bindParam(':type', $type);
        if ($stmt->execute()) {
            $result = $stmt->fetch(\PDO::FETCH_NUM);
            if ($result) {
                return $result[0];
            }
        }

        return 0;
    }

    public function fetchTargetIds($type) {
        $em = $this->doctrine->getManager();
        $result = $em->getRepository(CustomizeContent::class)->findBy([
            'type' => $type,
        ]);
        if ($result) {
            return ArrayUtility::columns($result, 'targetId');
        }

        return [];
    }
}

/**
 * Class DuplicateCustomizeContentException
 * @package Lychee\Bundle\AdminBundle\Service
 */
class DuplicateCustomizeContentException extends \Exception {

    public function __construct($type) {
        if ($type === CustomizeContent::TYPE_TOPIC) {
            $entity = '次元';
        } else {
            $entity = '用户';
        }
        parent::__construct($entity . '已存在');
    }
}