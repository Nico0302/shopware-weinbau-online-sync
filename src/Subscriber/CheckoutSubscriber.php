<?php declare(strict_types=1);

namespace WeinbauOnlineSync\Subscriber;

use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Checkout\Cart\CartEvents;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use WeinbauOnlineSync\Service\ApiService;
use Psr\Log\LoggerInterface;

class CheckoutSubscriber implements EventSubscriberInterface
{
    /**
     * @var ApiService
     */
    private $apiService;

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        ApiService $apiService,
        SystemConfigService $systemConfigService,
        LoggerInterface $logger
    ) {
        $this->apiService = $apiService;
        $this->systemConfigService = $systemConfigService;
        $this->logger = $logger;
        
    }

    public static function getSubscribedEvents(): array
    {
        // Return the events to listen to as array like this:  <event to listen to> => <method to execute>
        return [
            CartEvents::CHECKOUT_ORDER_PLACED => 'onOrderPlaced'
        ];
    }

    public function onOrderPlaced(CheckoutOrderPlacedEvent $event)
    {
        $this->logger->error(
            \sprintf(
                'event failed',
                static::class,
                __METHOD__,
                static::class
            )
        );
        $this->apiService->newOrder($event->getOrder());
    }
}