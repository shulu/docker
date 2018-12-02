<?php
namespace Lychee\Component\Database;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Configuration;
use Doctrine\Common\EventManager;

class DefaultConnection extends Connection {

    /**
     * Initializes a new instance of the Connection class.
     *
     * @param array                              $params       The connection parameters.
     * @param \Doctrine\DBAL\Driver              $driver       The driver to use.
     * @param \Doctrine\DBAL\Configuration|null  $config       The configuration, optional.
     * @param \Doctrine\Common\EventManager|null $eventManager The event manager, optional.
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function __construct(array $params, Driver $driver, Configuration $config = null,
                                EventManager $eventManager = null) {
        if (!isset($params['charset'])) {
            $params['charset'] = 'UTF8MB4';
        }
        if (!isset($params['defaultTableOptions']) || empty($params['defaultTableOptions'])) {
            $params['defaultTableOptions'] = [
                'charset' => 'utf8mb4',
                'collate' => 'utf8mb4_general_ci',
                'engine' => 'InnoDB',
            ];
        }

        parent::__construct($params, $driver, $config, $eventManager);
    }

}