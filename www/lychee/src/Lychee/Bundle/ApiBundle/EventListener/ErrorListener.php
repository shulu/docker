<?php
namespace Lychee\Bundle\ApiBundle\EventListener;

use Lychee\Bundle\ApiBundle\Error\CommonError;
use Lychee\Bundle\ApiBundle\Error\ErrorsException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Lychee\Bundle\ApiBundle\Controller\Controller as ApiController;
use Lychee\Module\Notification\Push\PushService;

class ErrorListener implements EventSubscriberInterface {

    /**
     * @var LoggerInterface
     */
    private $logger;

    private $pusher;

    private $debug;

    /**
     * @param LoggerInterface $logger
     * @param PushService $pusher
     * @param bool $debug
     */
    public function __construct(LoggerInterface $logger, $pusher, $debug) {
        $this->logger = $logger;
        $this->pusher = $pusher;
        $this->debug = $debug;
    }

    public function onKernelException(GetResponseForExceptionEvent $event) {
        if ($event->getException() == null || $event->getResponse() != null) {
            return;
        }

        $exception = $event->getException();
        if ($exception instanceof NotFoundHttpException) {
            $request = $event->getRequest();
            $errors = array( CommonError::ApiNotFound($request->getRequestUri()) );
        } else if ($exception instanceof MethodNotAllowedHttpException) {
            $request = $event->getRequest();
            $allows = $exception->getHeaders()['Allow'];
            $errors = array( CommonError::MethodNotAllow($request->getMethod(), $allows) );
        } else if (!($exception instanceof ErrorsException)) {
            if ($this->logger) {
                $this->logger->emergency('Unknown Error', array(
                    'exception' => $exception,
                    'trace' => $exception->getTraceAsString()
                ));
            }
            if ($this->pusher) {
                try {
                    $this->pusher->reportError($exception->getMessage());
                } catch (\Exception $e) {
                    //do nothing
                }
            }
            $errors = array( CommonError::SystemError() );
            if ($this->debug) {
                return;
            }
        } else {
            $errors = $exception->getErrors();
        }

        $accepts = $event->getRequest()->getAcceptableContentTypes();
        if (in_array('text/html', $accepts)) {
            $response = new Response('发生错误.');
            $event->setResponse($response);
        } else {
            $response = ApiController::buildErrorsResponse($errors);
            $response->headers->set('X-Status-Code', 200);
            $event->setResponse($response);
        }
    }

    public static function getSubscribedEvents() {
        return array(
            KernelEvents::EXCEPTION => array('onKernelException'),
        );
    }

} 