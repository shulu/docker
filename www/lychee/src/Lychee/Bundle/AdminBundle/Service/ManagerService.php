<?php
namespace Lychee\Bundle\AdminBundle\Service;

use Doctrine\ORM\EntityManager;
use Lychee\Component\Foundation\ArrayUtility;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Lychee\Bundle\AdminBundle\Entity\Manager;
use Symfony\Component\Security\Core\Util\StringUtils;

class ManagerService implements UserProviderInterface {
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var PasswordEncoderInterface
     */
    private $passwordEncoder;

    /**
     * @var string
     */
    private $entityClassName;

    /**
     * @param EncoderFactory $encoderFactory
     * @param ManagerRegistry $registry
     * @param string $entityManagerName
     */
    public function __construct($encoderFactory, $registry, $entityManagerName = null) {
        $this->entityClassName = 'Lychee\\Bundle\\AdminBundle\\Entity\\Manager';
        $this->entityManager = $registry->getManager($entityManagerName);
        $this->passwordEncoder = $encoderFactory->getEncoder($this->entityClassName);
    }

    /**
     * @param string $email
     * @param string $name
     * @param string $password
     *
     * @return Manager
     */
    public function createManager($email, $name, $password) {
        $salt = base64_encode( openssl_random_pseudo_bytes(32) );
        $passwordEncoded = $this->passwordEncoder->encodePassword($password, $salt);

        $manager = new Manager();
        $manager->createTime = new \DateTime();
        $manager->email = $email;
        $manager->name = $name;
        $manager->salt = $salt;
        $manager->password = $passwordEncoded;

        $this->entityManager->persist($manager);
        $this->entityManager->flush($manager);
        return $manager;
    }

    /**
     * @param Manager $manager
     * @param string $name
     */
    public function updateManagerProfile($manager, $name) {
        $manager->name = $name;
        $this->entityManager->flush($manager);
    }

    /**
     * @param Manager $manager
     * @param string $password
     *
     * @return void
     */
    public function updateManagerPassword($manager, $password) {
        $passwordEncoded = $this->passwordEncoder->encodePassword($password, $manager->salt);
        $manager->password = $passwordEncoded;
        $this->entityManager->flush($manager);
    }

    /**
     * @param Manager $manager
     * @return void
     */
    public function freezeManager($manager) {
        $manager->frozen = true;
	    $this->update($manager);
    }

    /**
     * @param Manager $manager
     * @return void
     */
    public function activateManager($manager) {
        $manager->frozen = false;
	    $this->update($manager);
    }

    /**
     * @param string $email
     *
     * @return Manager|null
     */
    public function loadManagerByEmail($email) {
        $query = $this->entityManager->createQueryBuilder()
            ->select('m, r')
            ->from('LycheeAdminBundle:Manager', 'm')
            ->leftJoin('m.roles', 'r')
            ->where('m.email = :email')
            ->setParameter('email', $email)
            ->getQuery();

        return $query->getOneOrNullResult();
    }

    /**
     * @param string $username The username
     * @return UserInterface
     * @throws UsernameNotFoundException if the user is not found
     */
    public function loadUserByUsername($username) {
        $manager = $this->loadManagerByEmail($username);
        if ($manager === null) {
            $message = sprintf(
                'Unable to find an LycheeAdminBundle:Manager object identified by "%s".',
                $username
            );
            throw new UsernameNotFoundException($message, 0);
        }
        return $manager;
    }

    /**
     * @param UserInterface $user
     * @return UserInterface
     * @throws UnsupportedUserException if the account is not supported
     */
    public function refreshUser(UserInterface $user) {
        $class = get_class($user);
        if (!$this->supportsClass($class)) {
            throw new UnsupportedUserException(
                sprintf(
                    'Instances of "%s" are not supported.',
                    $class
                )
            );
        }

        return $this->entityManager->find('LycheeAdminBundle:Manager' , $user->id);
    }

    /**
     * @param string $class
     * @return bool
     */
    public function supportsClass($class) {
        return $this->entityClassName === $class ||
            is_subclass_of($class, $this->entityClassName);
    }

    /**
     * @return array
     */
    public function listManagers() {
        $managers = $this->entityManager->getRepository('LycheeAdminBundle:Manager')->findBy([], [
        	'id' => 'DESC'
        ]);

        return $managers;
    }

    /**
     * @param $id
     * @return null|Manager
     * @throws \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     */
    public function loadManager($id) {
        return $this->entityManager->getRepository('LycheeAdminBundle:Manager')->find($id);
    }

    /**
     * @param $manager
     * @param $password
     * @return bool
     */
    public function comparePassword($manager, $password) {
        $passwordEncoded = $this->passwordEncoder->encodePassword($password, $manager->salt);

        return StringUtils::equals($passwordEncoded, $manager->password);
    }

    /**
     * @param $manager
     */
    public function update($manager) {
        $this->entityManager->flush($manager);
    }

    public function fetchByIds($managerIds) {
        return ArrayUtility::mapByColumn($this->entityManager->getRepository(Manager::class)->findBy([
            'id' => $managerIds,
        ]), 'id');
    }

}