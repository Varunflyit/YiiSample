<?php
namespace app\helpers;
/**
 * @author Yoyon Cahyono <yoyoncahyono@gmail.com>
 */
class Fixture
{
    public static $navigations = [
        [
            'id' => 'dashboards',
            'title' => 'Dashboards',
            'type' => 'group',
            'children' => [
                [
                    'id' => 'dashboards.integration-status',
                    'title' => 'Integration Status',
                    'type' => 'basic',
                    'icon' => 'heroicons_outline:document-duplicate',
                    'link' => '/dashboards/integration-status',
                ],
                [
                    'id' => 'dashboards.products',
                    'title' => 'Products (coming soon)',
                    'type' => 'basic',
                    'icon' => 'heroicons_outline:chart-pie',
                    'link' => '/dashboards/products',
                ],
            ],
        ],
        [
            'id' => 'apps',
            'title' => 'Apps',
            'type' => 'group',
            'children' => [
                [
                    'id' => 'apps.pim',
                    'title' => 'PIM (coming soon)',
                    'type' => 'basic',
                    'icon' => 'heroicons_outline:academic-cap',
                    'link' => '/apps/pim',
                ],
            ],
        ],
        [
            'id' => 'sync-logs',
            'title' => 'Sync Logs',
            'type' => 'group',
            'children' => [
                [
                    'id' => 'sync-logs.products',
                    'title' => 'Products',
                    'type' => 'basic',
                    'icon' => 'heroicons_outline:shopping-cart',
                    'link' => '/sync-logs/products',
                ],
                [
                    'id' => 'sync-logs.inventory',
                    'title' => 'Inventory',
                    'type' => 'basic',
                    'icon' => 'heroicons_outline:chat-alt',
                    'link' => '/sync-logs/inventory',
                ],
                [
                    'id' => 'sync-logs.orders',
                    'title' => 'Orders',
                    'type' => 'basic',
                    'icon' => 'heroicons_outline:user-group',
                    'link' => '/sync-logs/orders',
                ],
                [
                    'id' => 'sync-logs.tracking',
                    'title' => 'Tracking',
                    'type' => 'basic',
                    'icon' => 'heroicons_outline:cloud',
                    'link' => '/sync-logs/tracking',
                ],
            ],
        ],
        [
            'id' => 'admin',
            'title' => 'Admin',
            'type' => 'group',
            'children' => [
                [
                    'id' => 'admin.tenants',
                    'title' => 'Tenants',
                    'type' => 'basic',
                    'icon' => 'heroicons_outline:user-group',
                    'link' => '/tenants',
                ],
                [
                    'id' => 'admin.users',
                    'title' => 'Users',
                    'type' => 'basic',
                    'icon' => 'heroicons_outline:user-group',
                    'link' => '/users',
                ],
            ],
        ],
    ];
    public static $integrations = [
        [
            'integrationId' => '2afad8bd-7776-41ea-9a99-e7dc345c74ff',
            'name' => 'Maropost Commerce Cloud',
            'icon' => 'https://wordpress-631421-2579652.cloudwaysapps.com/wp-content/uploads/2022/07/44daee87-399d-45a9-b959-6ea26aedc153-2.png',
            'description' => 'Sync products, inventory, tracking and more to and from WooCommerce',
            'isActive' => true,
            'isCustom' => false,
        ],
        [
            'integrationId' => 'd39c5f3f-26bd-440b-8f99-f95869dfa659',
            'name' => 'Bunnings Marketlink',
            'icon' => 'https://wordpress-631421-2579652.cloudwaysapps.com/wp-content/uploads/2022/07/25498408_1747271388901154_6198955593483881874_n.png',
            'description' => 'Sync products, inventory, tracking and more to and from Bunnings Marketlink',
            'isCustom' => false,
        ]
    ];
    public static $syncLogs = [
        [
            'syncId' => 'df1e061d-b785-4168-ac18-489625071111',
            'installationId' => '0eb12ffd-34a8-491a-accc-df9b024f65f7',
            'integrationId' => '2afad8bd-7776-41ea-9a99-e7dc345c74ff',
            'niceDate' => '22-12-2019 10:15 PM',
            'sourceName' => 'Maropost',
            'sourceIcon' => 'https://wordpress-631421-2579652.cloudwaysapps.com/wp-content/uploads/2022/07/44daee87-399d-45a9-b959-6ea26aedc153-2.png',
            'neatSourceURL' => 'pshomeandliving.co.uk',
            'sourceId' => 'N21511',
            'destinationId' => 'N729104',
            'shortMessage' => 'Order successfully synced',
            'result' => [
                'badgeColor' => '#4fd1c5',
                'message' => 'Ok',
            ],
            'actionRequired' => false,
            'showSuggestSync' => false,
            'showResync' => true,
            'isCustom' => false,
            'detailView' => [
                'payload' => '',
                'response' => '',
                'detail1' => '',
            ],
        ],
        [
            'syncId' => 'df1e061d-b785-4168-ac18-489625071112',
            'installationId' => 'df1e061d-b785-4168-ac18-489625071b02',
            'integrationId' => 'c811f373-2053-427e-bc67-3ef7a3337a49',
            'niceDate' => '22-12-2019 10:15 PM',
            'sourceName' => 'WooCommerce',
            'sourceIcon' => 'https://wordpress-631421-2579652.cloudwaysapps.com/wp-content/uploads/2022/07/90431683_10158311076699180_2657409051876392960_n.png',
            'neatSourceURL' => 'onesixeightlondon.com.au',
            'sourceId' => 'AU611150',
            'destinationId' => 'N72112',
            'shortMessage' => 'Order synced with warning: ShipMethod Click & Collect does not exist.',
            'result' => [
                'badgeColor' => '#ffae42',
                'message' => 'Warning',
            ],
            'actionRequired' => false,
            'showSuggestSync' => false,
            'showResync' => true,
            'isCustom' => false,
            'detailView' => [
                'payload' => '',
                'response' => '',
                'detail1' => '',
            ],
        ],
        [
            'syncId' => 'df1e061d-b785-4168-ac18-489625071113',
            'installationId' => 'df1e061d-b785-4168-ac18-489625071b02',
            'integrationId' => 'c811f373-2053-427e-bc67-3ef7a3337a49',
            'niceDate' => '22-12-2019 10:15 PM',
            'sourceName' => 'WooCommerce 1',
            'sourceIcon' => 'https://wordpress-631421-2579652.cloudwaysapps.com/wp-content/uploads/2022/07/90431683_10158311076699180_2657409051876392960_n.png',
            'neatSourceURL' => 'onesixeightlondon.co.nz',
            'sourceId' => '',
            'destinationId' => '',
            'shortMessage' => 'Order syncing failed: could not reach WooCommerce',
            'result' => [
                'badgeColor' => '#de3a3a',
                'message' => 'Error',
            ],
            'actionRequired' => true,
            'showSuggestSync' => true,
            'showResync' => false,
            'isCustom' => false,
            'detailView' => [
                'payload' => '',
                'response' => '',
                'detail1' => '',
            ],
        ],
    ];
}