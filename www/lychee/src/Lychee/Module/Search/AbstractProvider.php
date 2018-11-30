<?php
namespace Lychee\Module\Search;

use FOS\ElasticaBundle\Provider\ProviderInterface;
use Lychee\Component\Foundation\CursorableIterator\QueryCursorableIterator;
use Lychee\Module\Search\Indexer;
use Lychee\Component\Foundation\CursorableIterator\CursorableIterator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Elastica\Exception\Bulk\ResponseException as BulkResponseException;

abstract class AbstractProvider implements ProviderInterface {

    private $indexer;
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @param Indexer $indexer
     * @param RegistryInterface $registry
     */
    public function __construct($indexer, $registry) {
        $this->indexer = $indexer;
        $this->em = $registry->getManager();
    }

    /**
     * Persists all domain objects to ElasticSearch for this provider.
     *
     * @param \Closure $loggerClosure
     * @param array    $options
     *
     * @return
     */
    function populate(\Closure $loggerClosure = null, array $options = array()) {
        $entitiesCount = $this->getEntitiesCount();
        $offset = isset($options['offset']) ? intval($options['offset']) : 0;
        $sleep = isset($options['sleep']) ? intval($options['sleep']) : 0;
        $batchSize = isset($options['batch-size']) ? intval($options['batch-size']) :
            isset($options['batch_size']) ? intval($options['batch_size']) : 200;
        $ignoreErrors = isset($options['ignore-errors']) ? $options['ignore-errors'] : $options['ignore_errors'];

        $iterator = $this->getCursorableIterator();
        $iterator->setStep($batchSize);
        $iterator->setCursor($offset);

        $finishedCount = 0;
        $stepEntitiesCount = 0;
        foreach ($iterator as $entities) {
            if ($loggerClosure) {
                $stepStartTime = microtime(true);
                $stepEntitiesCount = count($entities);
            }

            if (!$ignoreErrors) {
                $this->indexer->add($entities);
            } else {
                try {
                    $this->indexer->add($entities);
                } catch(BulkResponseException $e) {
                    if ($loggerClosure) {
                        $loggerClosure($stepEntitiesCount, $entitiesCount, sprintf('<error>%s</error>',$e->getMessage()));
                    }
                }
            }

            $this->em->clear();
            usleep($sleep);

            if ($loggerClosure) {
                $loggerClosure($stepEntitiesCount, $entitiesCount);
            }
        }
    }

    /**
     * @return string
     */
    abstract protected function getClassName();

    /**
     * @return CursorableIterator
     */
    abstract protected function getCursorableIterator();

    /**
     * @return int
     */
    protected function getEntitiesCount() {
        $query = $this->em->createQuery(sprintf('SELECT COUNT(t) FROM %s t', $this->getClassName()));
        return intval($query->getSingleScalarResult());
    }

    protected function getMemoryUsage() {
        $memory = round(memory_get_usage() / (1024 * 1024)); // to get usage in Mo
        $memoryMax = round(memory_get_peak_usage() / (1024 * 1024)); // to get max usage in Mo

        return sprintf('(RAM : current=%uMo peak=%uMo)', $memory, $memoryMax);
    }

}