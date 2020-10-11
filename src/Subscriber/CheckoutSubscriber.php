<?php declare(strict_types=1);

namespace WeinbauOnlineSync\Subscriber;

use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Checkout\Cart\CartEvents;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use WeinbauOnlineSync\Service\ApiService;

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

    public function __construct(ApiService $apiService, SystemConfigService $systemConfigService)
    {
        $this->apiService = $apiService;
        $this->systemConfigService = $systemConfigService;
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
        $this->$apiService->newOrder($event->getOrder());
    }
}