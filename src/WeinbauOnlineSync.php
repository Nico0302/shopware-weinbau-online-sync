<?php declare(strict_types=1);

namespace WeinbauOnlineSync;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\CustomField\CustomFieldTypes;

if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
}

class WeinbauOnlineSync extends Plugin
{

    public function install(InstallContext $context): void
    {
        parent::install($context);

        /** @var EntityRepositoryInterface $customFieldSetRepository */
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');

        $customFieldSetRepository->upsert([[
            'id' => Uuid::randomHex(),
            'name' => 'weinbau_online',
            'config' => [
                'label' => [
                    'en-GB' => 'Weinbau Online'
                ]
            ],
            'customFields' => [
                [
                    'name' => 'wbo_product_id',
                    'type' => CustomFieldTypes::INT,
                    'config' => [
                        'label' => [
                            'en-GB' => 'Product No.',
                            'de-DE' => 'Artikel Nr.'
                        ],
                        'customFieldPosition' => 1
                    ]
                ]
            ],
            'relations' => [
                [
                    'entityName' => ProductDefinition::ENTITY_NAME
                ],
            ]
        ]], $context->getContext());
    }
}