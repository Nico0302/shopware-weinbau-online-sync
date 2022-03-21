<?php declare(strict_types=1);

namespace WeinbauOnlineSync\Service;

use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Checkout\Order\OrderEntity;
use GuzzleHttp\Client;
use VIISON\AddressSplitter\AddressSplitter;
use function Sodium\add;

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

    private const WBO_SALUTATIONS = [
        'salutationMs' => 2,
        'salutationMr' => 1,
        'salutationCompany' => 3
    ];

    /**
     * @var Client
     */
    private Client $restClient;

    /**
     * @var SystemConfigService
     */
    private SystemConfigService $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->restClient = new Client();
        $this->systemConfigService = $systemConfigService;
    }

    public function newOrder(OrderEntity $order)
    {
        $transaction = $order->getTransactions()->first();
        $paymentMethod = $this->getPaymentMethod($transaction->getPaymentMethodId());
        $billingAddress = $order->getBillingAddress();
        $lineItems = $order->getLineItems();

        $params = [
            'anrede' => $this->getSalutation($billingAddress->getSalutationId()),
            'telefon' => $billingAddress->getPhoneNumber(),
            'email' => $order->getOrderCustomer()->getEmail(),
            'referenz' => $order->getCustomerComment(),
            'zahlungsart' => $paymentMethod,
            'versandkosten' => $order->getShippingTotal(),
            'gebuehr' => 0,
            'positionen' => $lineItems->count()
        ];

        $params = array_merge($params, $this->getAddress($billingAddress));

        $itemIndex = 0;
        foreach ($lineItems as $lineItem) {
            $params['wein_anzahl[' . itemIndex . ']'] = $lineItem->getQuantity();
            $params['wein_id[' . $itemIndex . ']'] = $lineItem->getProduct()->getProductNumber();
            $itemIndex++;
        }

        $deliveryAddress = null;
        // delivery address should only be transmitted if it is different from the billing address
        foreach ($order->getAddresses() as $address) {
            if ($address->getId() != $billingAddress->getId()) {
                $deliveryAddress = $address;
            }
        }
        if ($deliveryAddress != null) {
            $deliveryAddressArray = $this->getAddress($deliveryAddress);
            // Add 'l_' prefix
            $params = array_merge($params, array_combine(array_map(function ($key) {
                return 'l_' . $key;
            }, array_keys($deliveryAddressArray)), $deliveryAddressArray));
        }

        //$this->callApi('newOrder', $params);
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
            self::API_URL . http_build_query(array_merge($defaultParams, $params))
        );
    }

    private function getConfig(string $key)
    {
        return $this->systemConfigService->get('WeinbauOnlineSync.config.' . $key);
    }

    private function getPaymentMethod(string $paymentMethodId): ?int
    {
        foreach (self::WBO_PAYMENT_METHODS as $methodName => $methodNumber) {
            $methodIds = $this->getConfig($methodName);
            if (in_array($paymentMethodId, $methodIds))
                return $methodNumber;
        }
        return null;
    }

    private function getSalutation(string $salutationId): ?int
    {
        foreach (self::WBO_SALUTATIONS as $salutationName => $salutationNumber) {
            $salutationIds = $this->getConfig($salutationName);
            if (in_array($salutationId, $salutationIds))
                return $salutationNumber;
        }
        return null;
    }

    private function getAddress(OrderAddressEntity $address)
    {
        $splitStreetName = AddressSplitter::splitAddress($address->getStreet());

        return [
            'firma' => $address->getCompany(),
            'name' => $address->getFirstName(),
            'nname' => $address->getLastName(),
            'strasse' => $address->getStreet(),
            'hnummer' => $splitStreetName["houseNumber"],
            'land' => $address->getCountry()->getIso(),
            'plz' => $address->getZipcode(),
            'ort' => $address->getCity(),
        ];
    }
}