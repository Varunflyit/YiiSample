<?php

namespace app\common\components;

use Yii;
use yii\base\Component;
use yii\httpclient\Client;
use yii\httpclient\Exception;
use yii\base\InvalidArgumentException;
use yii\helpers\Json;
use yii\helpers\ArrayHelper;

/**
 * Class Neto
 *
 * @see https://developers.neto.com.au/documentation/engineers/api-documentation
 * @package common\components
 */
class Neto extends Component
{
    /** @var string neto site url */
    public $siteUrl;
    /** @var string password */
    public $apiKey;
    /** @var string username */
    public $username;
    /** @var array bill info */
    public $billInfo;
    /** @var integer max retry on error */
    public $maxRetry = 1;

    public const ORDER_ELEMENTS = [
        'ID','ComponentOfKit','KitPartID','ShippingOption','SeliveryInstruction','RelatedOrderID','Username','Email','ShipAddress','BillAddress','PurchaseOrderNumber','SalesPerson','CustomerRef1','CustomerRef2','CustomerRef3','CustomerRef4','CustomerRef5','CustomerRef6','CustomerRef7','CustomerRef8','CustomerRef9','CustomerRef10','SalesChannel','GrandTotal','TaxInclusive','OrderTax','SurchargeTotal','SurchargeTaxable','ProductSubtotal','ShippingTotal','ShippingTax','ClientIPAddress','CouponCode','CouponDiscount','ShippingDiscount','OrderType','OnHoldType','OrderStatus','OrderPayment','OrderPayment.PaymentType','DateUpdated','DatePlaced','DateRequired','DateInvoiced','DatePaid','DateCompleted','DateCompletedUTC','DatePaymentDue','PaymentTerms','OrderLine','OrderLine.ProductName','OrderLine.ItemNotes','OrderLine.SerialNumber','OrderLine.PickQuantity','OrderLine.BackorderQuantity','OrderLine.UnitPrice','OrderLine.Tax','OrderLine.TaxCode','OrderLine.WarehouseID','OrderLine.WarehouseName','OrderLine.WarehouseReference','OrderLine.Quantity','OrderLine.PercentDiscount','OrderLine.ProductDiscount','OrderLine.CouponDiscount','OrderLine.CostPrice','OrderLine.ShippingMethod','OrderLine.ShippingServiceID','OrderLine.ShippingServiceName','OrderLine.ShippingTracking','OrderLine.ShippingCarrierCode','OrderLine.ShippingCarrierName','OrderLine.ShippingTrackingUrl','OrderLine.Weight','OrderLine.Cubic','OrderLine.Extra','OrderLine.ExtraOptions','OrderLine.BinLoc','OrderLine.QuantityShipped','OrderLine.ExternalSystemIdentifier','OrderLine.ExternalOrderReference','OrderLine.ExternalOrderLineReference','ShippingSignature','RealtimeConfirmation','InternalOrderNotes','OrderLine.eBay.eBayUsername','OrderLine.eBay.eBayStoreName','OrderLine.eBay.eBayTransactionID','OrderLine.eBay.eBayAuctionID','OrderLine.eBay.ListingType','OrderLine.eBay.DateCreated','CompleteStatus','OrderLine.eBay.DatePaid','UserGroup','StickyNotes'
    ];
    public const PRODUCT_ELEMENTS = [
        'ParentSKU', 'ID', 'Brand', 'Model', 'Virtual', 'Name', 'PrimarySupplier', 'Approved', 'IsActive', 'IsNetoUtility', 'AuGstExempt', 'NzGstExempt', 'IsGiftVoucher', 'FreeGifts', 'CrossSellProducts', 'UpsellProducts', 'PriceGroups', 'ItemLength', 'ItemWidth', 'ItemHeight', 'ShippingLength', 'ShippingWidth', 'ShippingHeight', 'ShippingWeight', 'CubicWeight', 'HandlingTime', 'WarehouseQuantity', 'WarehouseLocations', 'CommittedQuantity', 'AvailableSellQuantity', 'ItemSpecifics', 'Categories', 'AccountingCode', 'SortOrder1', 'SortOrder2', 'RRP', 'DefaultPrice', 'PromotionPrice', 'PromotionStartDate', 'PromotionStartDateLocal', 'PromotionStartDateUTC', 'PromotionExpiryDate', 'PromotionExpiryDateLocal', 'PromotionExpiryDateUTC', 'DateArrival', 'DateArrivalUTC', 'CostPrice', 'UnitOfMeasure', 'BaseUnitOfMeasure', 'BaseUnitPerQuantity', 'QuantityPerScan', 'BuyUnitQuantity', 'SellUnitQuantity', 'PreOrderQuantity', 'PickPriority', 'PickZone', 'eBayProductIDs', 'TaxCategory', 'TaxFreeItem', 'TaxInclusive', 'SearchKeywords', 'ShortDescription', 'Description', 'Features', 'Specifications', 'Warranty', 'eBayDescription', 'TermsAndConditions', 'ArtistOrAuthor', 'Format', 'ModelNumber', 'Subtitle', 'AvailabilityDescription', 'Images', 'ImageURL', 'BrochureURL', 'ProductURL', 'DateAdded', 'DateAddedLocal', 'DateAddedUTC', 'DateCreatedLocal', 'DateCreatedUTC', 'DateUpdated', 'DateUpdatedLocal', 'DateUpdatedUTC', 'UPC', 'UPC1', 'UPC2', 'UPC3', 'Type', 'SubType', 'NumbersOfLabelsToPrint', 'ReferenceNumber', 'InternalNotes', 'BarcodeHeight', 'SupplierItemCode', 'SplitForWarehousePicking', 'DisplayTemplate', 'EditableKitBundle', 'RequiresPackaging', 'IsAsset', 'WhenToRepeatOnStandingOrders', 'SerialTracking', 'Group', 'ShippingCategory', 'MonthlySpendRequirement', 'RestrictedToUserGroup', 'IsInventoried', 'IsBought', 'IsSold', 'ExpenseAccount', 'PurchaseTaxCode', 'CostOfSalesAccount', 'IncomeAccount', 'AssetAccount', 'KitComponents', 'SEOPageTitle', 'SEOMetaKeywords', 'SEOPageHeading', 'SEOMetaDescription', 'SEOCanonicalURL', 'ItemURL', 'AutomaticURL', 'Job', 'RelatedContents', 'SalesChannels', 'Misc01', 'Misc02', 'Misc03', 'Misc04', 'Misc05', 'Misc06', 'Misc07', 'Misc08', 'Misc09', 'Misc10', 'Misc11', 'Misc12', 'Misc13', 'Misc14', 'Misc15', 'Misc16', 'Misc17', 'Misc18', 'Misc19', 'Misc20', 'Misc21', 'Misc22', 'Misc23', 'Misc24', 'Misc25', 'Misc26', 'Misc27', 'Misc28', 'Misc29', 'Misc30', 'Misc31', 'Misc32', 'Misc33', 'Misc34', 'Misc35', 'Misc36', 'Misc37', 'Misc38', 'Misc39', 'Misc40', 'Misc41', 'Misc42', 'Misc43', 'Misc44', 'Misc45', 'Misc46', 'Misc47', 'Misc48', 'Misc49', 'Misc50', 'Misc51', 'Misc52'
    ];

    /**
     * @inheritdoc
     */
    public function init()
    {
        if (empty($this->siteUrl)) {
            throw new InvalidArgumentException('Site Url should be set');
        }
        if (empty($this->apiKey)) {
            throw new InvalidArgumentException('Api Key should be set');
        }
        if (empty($this->username)) {
            throw new InvalidArgumentException('Username should be set');
        }
    }

    /**
     * Send and returns response data
     *
     * @param string $action request action
     * @param string $method request method
     * @param array $data request data
     * @return array
     */
    public function sendRequest($action, $method = 'get', $data = null)
    {
        $url = $this->getBaseUrl();
        $response = $this->sendSignedRequest($url, $action, $method, $data);
        if ($response->isOk) {
            $data = $response->data;
        } else {
            throw new Exception($response->content);
        }
        return $data;
    }

    /**
     * Send a signed request with retry mechanism
     * @param string $url requested url
     * @param string $action request action
     * @param string $method request method
     * @param array $data request data
     * @return \yii\httpclient\Response
     */
    protected function sendSignedRequest($url, $action, $method = 'get', $data = null)
    {
        $tries = 1;
        $client = new Client([
            'transport' => 'yii\httpclient\CurlTransport'
        ]);
        $request = $client->createRequest()
            ->addHeaders([
                'NETOAPI_ACTION' => $action,
                'NETOAPI_USERNAME' => $this->username,
                'NETOAPI_KEY' => $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])
            ->setMethod($method)
            ->setUrl($url);
        if (is_array($data)) {
            $request->setData($data);
        } elseif (is_string($data)) {
            $request->setContent($data);
        }
        while (true) {
            $response = $request->send();
            if ($response->isOk) {
                return $response;
            } else {
                // delete access key cache
                if ($tries < $this->maxRetry) {
                    sleep($tries);
                    $tries++;
                } else {
                    return $response;
                }
            }
        }
    }

    /**
     * Return neto base url
     * @return string
     */
    protected function getBaseUrl()
    {
        return $this->siteUrl . 'do/WS/NetoAPI';
    }

    /**
     * Return list of neto orders
     * @param string|null $username filter by username
     * @return array
     */
    public function getOrders($username = null)
    {
        $data = [
            'Filter' => array_filter([
                'Username' => $username,
                'OutputSelector' => [
                    'Username',
                    'Email',
                    'BillAddress',
                    'ShipAddress',
                    'DeliveryInstruction',
                    'DatePaid',
                    'DateInvoiced',
                    'DatePlaced',
                    'DateCompleted',
                    'OrderStatus',
                    'PurchaseOrderNumber',
                    'OrderLine',
                    'OrderLine.PickQuantity',
                    'OrderLine.Quantity',
                    'OrderLine.ProductName',
                    'OrderLine.UnitPrice',
                    'OrderLine.ShippingMethod',
                    'OrderLine.ShippingTracking',
                    'OrderLine.QuantityShipped',
                    'ShippingSignature',
                    'InternalOrderNotes',
                    'OrderLine.ExternalSystemIdentifier',
                    'OrderLine.ExternalOrderReference',
                    'OrderLine.ExternalOrderLineReference',
                ]
            ])
        ];
        $result = $this->sendRequest('GetOrder', 'post', Json::encode($data));
        return ArrayHelper::getValue($result, 'Order');
    }

    /**
     * Return neto order based on known id
     * @param string $id order id
     * @param string|null $username filter by username
     * @return array
     */
    public function getOrderById($id, $username = null)
    {
        $data = [
            'Filter' => array_filter([
                'OrderID' => $id,
//                 'Username' => $username,
                'OutputSelector' => [
                    'Username',
                    'Email',
                    'SalesChannel',
                    'ClientIPAddress',
                    'BillAddress',
                    'ShipAddress',
//                     'DeliveryInstruction',
//                     'DatePaid',
//                     'DateInvoiced',
//                     'DatePlaced',
//                     'DateCompleted',
//                     'OrderStatus',
                    'PurchaseOrderNumber',
                    'OrderLine',
                    'OrderLine.ItemNotes',
                    'OrderLine.Extra',
                    'OrderLine.ExtraOptions',
                    // 'OrderLine.PickQuantity',
                    'OrderLine.Quantity',
                    // 'OrderLine.ProductName',
//                     'OrderLine.UnitPrice',
                    'OrderLine.ShippingMethod',
                    'OrderLine.ShippingTracking',
//                     'OrderLine.QuantityShipped',
//                     'ShippingSignature',
//                     'GrandTotal',
//                     'ShippingTotal',
//                     'ShippingDiscount',
//                     'InternalOrderNotes',
//                     'OrderLine.ExternalSystemIdentifier',
                    'OrderLine.ExternalOrderReference',
                    'OrderLine.ExternalOrderLineReference',
                ]
            ])
        ];
        $result = $this->sendRequest('GetOrder', 'post', Json::encode($data));
        return ArrayHelper::getValue($result, 'Order.0');
    }

    /**
     * Return neto order based on known external reference
     * @param string $ref reference
     * @param string|null $username filter by username
     * @return array
     */
    public function getOrderByExternalReference($ref, $username = null, $orderStatus = null)
    {
        $data = [
            'Filter' => array_filter([
                'ExternalOrderReference' => $ref,
//                 'PurchaseOrderID' => $ref,
                'Username' => $username,
                'OutputSelector' => [
                    'Username',
                    'Email',
                    'BillAddress',
                    'ShipAddress',
                    'DeliveryInstruction',
                    'DatePaid',
                    'DateInvoiced',
                    'DatePlaced',
                    'DateCompleted',
                    'OrderStatus',
                    'PurchaseOrderNumber',
                    'OrderLine',
                    'OrderLine.Extra',
                    'OrderLine.ExtraOptions',
                    'OrderLine.PickQuantity',
                    'OrderLine.Quantity',
                    'OrderLine.ProductName',
                    'OrderLine.UnitPrice',
                    'OrderLine.ShippingMethod',
                    'OrderLine.ShippingTracking',
                    'OrderLine.QuantityShipped',
                    'ShippingSignature',
                    'InternalOrderNotes',
                    'OrderLine.ExternalSystemIdentifier',
                    'OrderLine.ExternalOrderReference',
                    'OrderLine.ExternalOrderLineReference',
                    'OrderPayment'
                ]
            ])
        ];
        $result = $this->sendRequest('GetOrder', 'post', Json::encode($data));
        return ArrayHelper::getValue($result, 'Order.0');
    }

    /**
     * Return list of neto products
     * @param array $outputSelector product output selector
     * @param string $sku SKU
     * @param boolean $isActive is active product
     * @param boolean $approved approved product
     * @return array
     */
    public function getProducts($outputSelector = [], $sku = null, $limit = 0, $page = 0, $isActive = true, $approved = true)
    {
        $data = [
            'Filter' => array_filter([
                'SKU' => $sku,
                'Page' => $page,
                'Limit' => $limit,
                'Approved' => $approved,
                'IsActive' => $isActive,
                'OutputSelector' => $outputSelector
            ])
        ];
        $result = $this->sendRequest('GetItem', 'post', Json::encode($data));
        // var_dump($result, $data); die();
        return ArrayHelper::getValue($result, 'Item');
    }

    /**
     * Return neto product based on known sku
     * @param string $sku product sku
     * @return array
     */
    public function getProductBySKUOld($sku)
    {
        $data = [
            'Filter' => array_filter([
                'SKU' => $sku,
                'OutputSelector' => [
                    'Name', // If Misc10 not exists we use this
                    'Brand',
                    'Misc08', // Name,
                    'Misc01', // Categories,
                    'Misc02', // logistic-class  [only send the CODE i.e. xxxxx (FLAT)
                    'Misc04', // List to Catch Marketplace
                    'Misc07', // Remove from Catch Marketplace
                    'Misc03', // Club Catch Eligible
//                     'Images',
//                     'Approved', // need to be approved
//                     'IsActive', // need to be active
                    'PriceGroups', //price group should be Catch Marketplace, if Catch Price = 0, Pull in RRP]
//                     'RRP',
//                     'PromotionStartDate',
//                     'PromotionExpiryDate',
//                     'AvailableSellQuantity',
//                     'DefaultPrice',
//                     'ShortDescription', // Default for description
//                     'SEOMetaDescription', // Alternative description if ShortDescription is empty
//                     'Description', // Alternative description if ShortDescription and SEOMetaDescription is empty

//                     'ShippingCategory',
//                     'Misc10',
//                     'Misc25',
//                     'Misc28',
//                     'Misc30',
//                     'Misc26',
//                     'Misc29',
//                     'Misc35',
//                     'ParentSKU',
//                     'variate_product',
//                     'ID',
//                     'Brand',
//                     'Model',
//                     'Virtual',
//                     'Name',
//                     'Categories',
//                     'ItemSpecifics',
//                     'Images',
//                     'ImageURL',
//                     'PrimarySupplier',
//                     'Approved',
//                     'IsActive',
//                     'PriceGroups',
//                     'WarehouseQuantity',
//                     'CommittedQuantity',
//                     'AvailableSellQuantity',
//                     'RRP',
//                     'DefaultPrice',
//                     'PromotionStartDate',
//                     'PromotionExpiryDate',
//                     'CostPrice',
//                     'UPC',
//                     'Description',
//                     'ShortDescription',
//                     'SEOMetaDescription',
//                     'eBayDescription',
                ]
            ])
        ];
        $result = $this->sendRequest('GetItem', 'post', Json::encode($data));
        return ArrayHelper::getValue($result, 'Item.0');
    }

    /**
     * Add neto customers
     * @return array
     */
    public function addCustomer($cutomer)
    {
        $result = $this->sendRequest('AddCustomer', 'post', Json::encode($cutomer));
        return $result;
        return ArrayHelper::getValue($result, 'Customer');
    }

    /**
     * Return list of neto customers
     * @return array
     */
    public function getCustomers()
    {
        $data = [
            'Filter' => array_filter([
                'Type' => 'Customer',
                'OutputSelector' => [
                    'Username',
                    'Type',
                    'EmailAddress',
                    'BillingAddress',
                ]
            ])
        ];
        $result = $this->sendRequest('GetCustomer', 'post', Json::encode($data));
        return ArrayHelper::getValue($result, 'Customer');
    }

    /**
     * Return list of shipping methods
     * @return array
     */
    public function getShippingMethods()
    {
        $result = $this->sendRequest('GetShippingMethods', 'post', '{}');
        return ArrayHelper::getValue($result, 'ShippingMethods');
    }

    /**
     * Return product category names
     * @param array $product product attributes
     * return array
     */
    public static function productCategoryNames($product)
    {
        $categoryNames = [];
        $categories = ArrayHelper::getValue($product, 'Categories');
        foreach ($categories as $categoriesItem) {
            $category = ArrayHelper::getValue($categoriesItem, 'Category');
            foreach ($category as $categoryItem) {
                $name = ArrayHelper::getValue($categoryItem, 'CategoryName');
                if (!empty($name)) {
                    $categoryNames[] = $name;
                }
            }
        }
        return $categoryNames;
    }

    /**
     * Return product price group value
     * @param array $product product attributes
     * @param string $groupName group name
     * @param string $pricing pricing name
     * return string
     */
    public static function productGroupPrice($product, $groupName, $pricing = 'Price')
    {
        $defaultPrice = 0;
        $priceGroups = ArrayHelper::getValue($product, 'PriceGroups') ?? [];        
        foreach ($priceGroups as $priceGroupsItem) {            
            $priceGroup = ArrayHelper::getValue($priceGroupsItem, 'PriceGroup') ?? [];
            if (!empty($priceGroups) && ArrayHelper::isAssociative($priceGroup)) {
                $priceGroup = [$priceGroup];
            }
            foreach ($priceGroup as $priceItem) {                
                $id = ArrayHelper::getValue($priceItem, 'GroupID');
                $name = ArrayHelper::getValue($priceItem, 'Group');
                $price = ArrayHelper::getValue($priceItem, $pricing);
                if (is_integer($groupName) && $groupName == $id) {
                    return $price;
                } elseif ($groupName === $name) {
                    return $price;
                }
            }
        }
        return $defaultPrice;
    }

    /**
     * Return product image url
     * @param array $product product attributes
     * @param string $imageName image name
     * return string
     */
    public static function productImageUrl($product, $imageName = 'Main')
    {
        $images = ArrayHelper::getValue($product, 'Images');
        foreach ($images as $image) {
            $name = ArrayHelper::getValue($image, 'Name');
            $url = ArrayHelper::getValue($image, 'URL');
            if ($imageName === $name) {
                //return str_replace('https://', 'http://', $url);
                return str_replace('http://', 'https://', $url);
            }
        }
        return null;
    }

    public function getProductFromCache($sku)
    {
        $products = Yii::$app->cache->get('NetoProductCache::' . $this->apiKey);
        if (empty($products)) {
            $products = [];
        }
        return ArrayHelper::getValue($products, $sku);
    }

    public function addProductToCache($product)
    {
        $sku = ArrayHelper::getValue($product, 'SKU');
        $products = Yii::$app->cache->get('NetoProductCache::' . $this->apiKey);
        if (empty($products)) {
            $products = [];
        }
        $products[$sku] = $product;
        Yii::$app->cache->set('NetoProductCache::' . $this->apiKey, $products);
    }

    public function flushProductsCache()
    {
        Yii::$app->cache->delete('NetoProductCache::' . $this->apiKey);
    }

    /**
     * Return neto product based on known sku
     * @param string $sku product sku
     * @return array
     */
    public function getProductBySKU($sku, $useCache = false)
    {
        if ($useCache) {
            $product = $this->getProductFromCache($sku);
        }
        if (empty($product)) {
            $data = [
                'Filter' => array_filter([
                    'SKU' => $sku,
                    'OutputSelector' => static::PRODUCT_ELEMENTS
                ])
            ];
            $result = $this->sendRequest('GetItem', 'post', Json::encode($data));
            $product = ArrayHelper::getValue($result, 'Item.0');
            if ($useCache) {
                $this->addProductToCache($product);
            }
        }
        return $product;
    }
}
