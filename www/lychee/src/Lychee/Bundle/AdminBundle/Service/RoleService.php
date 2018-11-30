<?php
namespace Lychee\Bundle\AdminBundle\Service;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class RoleService {
    /**
     * @var EntityManager
     */
    private $entityManager;


    /**
     * @var string
     */
    private $entityClassName = 'Lychee\\Bundle\\AdminBundle\\Entity\\Role';

    /**
     * @param ManagerRegistry $registry
     * @param string $entityManagerName
     */
    public function __construct($registry, $entityManagerName = null) {
        $this->entityManager = $registry->getManager($entityManagerName);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function fetchRole($id) {
        return $this->entityManager->getRepository('LycheeAdminBundle:Role')->find($id);
    }

    /**
     * @return mixed
     */
    public function fetchRoles() {
        return $this->entityManager->getRepository('LycheeAdminBundle:Role')->findAll();
    }
} 