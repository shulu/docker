<?php
namespace app\module\contentmanagement\domain;

use app\module\contentmanagement\model\DomainWhiteListItem;
use \think\Facade\Cache;

class WhiteList
{

    /**
     * @param string $name
     * @param string $domain
     * @param int $type DomainType
     *
     * @return DomainWhiteListItem
     * @throws \LogicException
     * @throws \RuntimeException
     */
    public function add($name, $domain, $type) {
        $componentsCount = count(explode('.', $domain));
        if ($componentsCount > 6 || $componentsCount < 2) {
            throw new \LogicException('invalid domain');
        }

        try {
            $item = new DomainWhiteListItem();
            $item->name = $name;
            $item->domain = $domain;
            $item->type = $type;

            $this->em->persist($item);
            $this->em->flush();
            return $item;
        } catch(UniqueConstraintViolationException $e) {
            throw new \LogicException('same name or same domain exists.', 0, $e);
        } catch (\Exception $e) {
            throw new \RuntimeException('something bad happen', 0, $e);
        }
    }

    /**
     * @param DomainWhiteListItem $item
     */
    public function remove($item) {
        $this->em->remove($item);
        $this->em->flush($item);
    }

    /**
     * @param string $domain
     *
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function contain($domain) {
        $statement = $this->em->getConnection()->executeQuery(
            'SELECT 1 FROM domain_whitelist WHERE domain = ? LIMIT 1',
            array($domain), array(\PDO::PARAM_STR));
        if ($statement->rowCount() > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function isValid($domain) {
        $components = explode('.', strtolower($domain));
        if (count($components) > 6 || count($components) < 2) {
            return false;
        }
        if (count($components) == 2) {
            array_unshift($components, 'www');
        }

        $domains = array();
        while (count($components) >= 2) {
            $domains[] = implode('.', $components);
            array_shift($components);
        }
		$domain = implode(' or ', array_pad(array(), count($domains), 'domain=?'));
        $where = ['domain'=>$domain];
        $count = DomainWhiteListItem::where($where)->count();
        #$sql = 'SELECT 1 FROM domain_whitelist WHERE ' . implode(' or ', array_pad(array(), count($domains), 'domain=?')) . ' LIMIT 1';
        #$statement = $this->em->getConnection()->executeQuery($sql, $domains);
        if ($count > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param int $page
     * @param int $count
     *
     * @return DomainWhiteListItem[]
     */
    public function getItemsByPage($page, $count) {
        return $this->em->getRepository(DomainWhiteListItem::class)
            ->findBy(array(), null, $count, ($page - 1) * $count);
    }

    /**
     * @return DomainWhiteListItem[]
     */
    public function getAllItems() {
        return $this->em->getRepository(DomainWhiteListItem::class)->findAll();
    }

    /**
     * @return int
     */
    public function getCount() {
        $query = $this->em->getRepository(DomainWhiteListItem::class)->createQueryBuilder('w')
            ->select('COUNT(w.id) AS white_list_count')
            ->setMaxResults(1)
            ->getQuery();
        $result = $query->getOneOrNullResult();

        return (int)$result['white_list_count'];
    }
}