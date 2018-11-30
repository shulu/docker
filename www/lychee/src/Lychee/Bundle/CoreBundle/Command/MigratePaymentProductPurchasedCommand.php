<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 2017/11/3
 * Time: 下午1:50
 */

namespace Lychee\Bundle\CoreBundle\Command;


use Doctrine\ORM\EntityManager;
use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Module\Payment\Entity\PaymentProductPurchased;
use Lychee\Module\Payment\Entity\PaymentTransaction;
use Lychee\Module\Payment\PurchaseRecorder;
use Lychee\Module\Payment\TransactionService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigratePaymentProductPurchasedCommand extends ContainerAwareCommand {
	use ModuleAwareTrait;

	protected function configure() {
		$this
			->setName( 'lychee:migration:payment-product-purchased' )
			->setDefinition( array() )
			->setDescription( 'Migrate payment-product-purchased\'s pay_type.' )
			->setHelp( <<<EOT
This command will migrate payment-product-purchased's pay_type.
EOT
			);
	}

	protected function execute( InputInterface $input, OutputInterface $output ) {

		/** @var EntityManager $entityManager */
		$entityManager = $this->container()->get( 'doctrine' )->getManager();
		$cursor        = PHP_INT_MAX;
		$count         = 1000;

		do {

			if ( ! $entityManager->isOpen() ) {
				$entityManager->getConnection()->connect();
			}
			$output->writeln( 'cursor: ' . $cursor );
			$records = $entityManager->createQueryBuilder()
			                         ->select( 'r' )
			                         ->from( PaymentProductPurchased::class, 'r' )
			                         ->where( 'r.id <= :cursor' )->setMaxResults( $count )
			                         ->andWhere( 'r.appStoreTransactionId = :appStoreTransactionId ' )
				                     ->andWhere('r.payType = :payType ')
			                         ->orderBy( 'r.id', 'DESC' )
			                         ->getQuery()
			                         ->execute( [ 'cursor' => $cursor, 'appStoreTransactionId' => '', 'payType' => '' ] );


			if ( empty( $records ) ) {
				break;
			}

			$transactionIds = ArrayUtility::columns( $records, 'transactionId' );
			$transactionIds = array_filter( $transactionIds );
			$transactions   = $entityManager->createQueryBuilder()
			                                ->select( 't' )
			                                ->from( PaymentTransaction::class, 't' )
			                                ->where( 't.id IN (:ids)' )
			                                ->getQuery()->execute( [ 'ids' => $transactionIds ] );
			$transactions   = ArrayUtility::mapByColumn( $transactions, 'id' );

			$entityManager->beginTransaction();
			foreach ( $records as $record ) {

				/** @var  $record PaymentProductPurchased */
				if ( ! isset( $transactions[ $record->transactionId ] ) ) {
					continue;
				}

				/** @var PaymentTransaction $transaction */
				$transaction = $transactions[ $record->transactionId ];

				$sql = 'UPDATE ciyo_payment.payment_product_purchased'
				       . ' SET pay_type = ?'
				       . ' WHERE id=?';
				$entityManager->getConnection()->executeUpdate(
					$sql,
					array( $transaction->payType, intval( $record->id ) ),
					array( \PDO::PARAM_STR, \PDO::PARAM_INT )
				);

			}
			$entityManager->commit();

			$record = $records[ count( $records ) - 1 ];
			$cursor = $record->id - 1;

		} while ( $cursor > 0 );

		$output->writeln( 'finished' );

	}

}