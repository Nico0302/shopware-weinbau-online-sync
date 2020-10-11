<?php declare(strict_types=1);

namespace WeinbauOnlineSync\Service;

use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Checkout\Order\OrderEntity;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class ApiService
{
    private const API_URL = 'https://nephele-s5.de/xml/v14.0/wbo-API.php';

    private const WBO_PAYMENT_METHODS = [
        'methodInvoice' => 1,
        'methodAdvance' => 2,
        'methodDebit' => 3,
        'methodCOD' => 4,
        'methodOnSite' => 5,
        'methodOnline' => 2 // XML-API Dokumentation: "Bei Paypal aktuell noch auf 2 setzen - gesonderte Zahlungsart hierfür folgt mit nächster Version"
    ];

    /**
     * @var Client
     */
    private $restClient;

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->restClient = new Client();
        $this->systemConfigService = $systemConfigService;
    }

    public function newOrder(OrderEntity $order)
    {
        $shipping = $order->getShippingTotal();
        $comment = $order->getCustomerComment();
        $transaction = $order->getTransactions()->first();
        $paymentMethod = $this->getPaymentMethod($transaction->getPaymentMethodId());
    }

    private function callApi(string $action, $params)
    {
        $defaultParams = [
            'UID' => $this->getConfig('userID'),
            'apiUSER' => $this->getConfig('email'),
            'apiCODE' => $this->getConfig('password'),
            'apiShopID' => 1,
            'apiACTION' => $action
        ];
        $response = $this->restClient->request(
            'POST',
            $this->API_URL.http_build_query(array_merge($defaultParams, $params))
        );
    }

    private function getConfig(string $key)
    {
        return $this->systemConfigService->get('WeinbauOnlineSync.config.' . $key);
    }

    private function getPaymentMethod(string $paymentMethodId)
    {
        foreach ($this->WBO_PAYMENT_METHODS as $methodName => $methodNumber) {
            $methodIds = $this->getConfig($methodName);
            if (in_array($paymentMethodId, $methodIds))
                return $methodNumber;
        }
        return null;
    }
}