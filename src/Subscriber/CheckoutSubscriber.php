<?php declare(strict_types=1);

namespace WeinbauOnlineSync\Subscriber;

use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Checkout\Cart\CartEvents;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class CheckoutSubscriber implements EventSubscriberInterface
{
    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService)
    {
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
        //$exampleConfig = $this->systemConfigService->get('ReadingPluginConfig.config.example'); 

        $shipping = $event->getShippingTotal();
        $comment = $event->getCustomerComment();
        $transaction = $event->getTransactions()->first();
        // Do something
        // E.g. work with the loaded entities: $event->getEntities()
    }
}