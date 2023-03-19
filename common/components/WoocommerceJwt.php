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
 * Class Woocommerce
 *
 * @see http://woocommerce.github.io/woocommerce-rest-api-docs/
 * @package common\components
 */
class WoocommerceJwt extends Component
{
    /** @var string woocommerce API endpoint url */
    public $endpoint;
    /** @var string key */
    public $key;
    /** @var string secret */
    public $secret;
    /** @var array access token */
    public $_token;
    /** @var array jwt_username */
    public $jwt_username;
    /** @var array jwt_password */
    public $jwt_password;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if (empty($this->endpoint)) {
            throw new InvalidArgumentException('Endpoint should be set');
        }
        if (empty($this->jwt_username)) {
            throw new InvalidArgumentException('JWT Username should be set');
        }
        if (empty($this->jwt_password)) {
            throw new InvalidArgumentException('JWT Password should be set');
        }
    }

    /**
     * Return woocommerce base url for basic
     * 
     * @param string $resource resource
     * 
     * @return string
     */
    protected function getBaseWpJson($resource)
    {
        return $this->endpoint . '/wp-json/' . $resource;
    }

    /**
     * Send a signed request with retry mechanism
     * 
     * @param string $url requested url
     * @param string $method request method
     * @param array $data request data
     * 
     * @return array
     */
    public static function sendJwtRequest($ls_token, $ls_curlUrl, $resource, $method = 'get', $data = null, $debug = false)
    {
        if (in_array('wp-json', explode('/', $resource[0]))) {

            $la_curlUrl = [$ls_curlUrl.$resource[0]];
            array_shift($resource);
            $la_curlUrl = array_merge($la_curlUrl,$resource);
            $ls_curlUrl = $la_curlUrl;
        } else {
            if (is_array($resource)) {            
                $ls_curlUrl = $ls_curlUrl.'/wp-json/'.$resource[0];
            } else {
                $ls_curlUrl = $ls_curlUrl.'/wp-json/'.$resource;
            }
        }

        $client = new Client([
            'transport' => 'yii\httpclient\CurlTransport'
        ]);
        $request = $client->createRequest()
            ->addHeaders([
                'Authorization' => 'Bearer ' . $ls_token,
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ])
            ->setMethod($method)
            ->setUrl($ls_curlUrl);

        if (is_array($data)) {
            $request->setData($data);
        } elseif (is_string($data)) {
            $request->setContent($data);
        }

        if ($debug) {
            var_dump($request->getFullUrl(),$request->getMethod(), $request->getHeaders(), $request->getContent());
            exit();
        }
        
        $response = $request->send();
        
        return $response->getData();
    }

    private function token()
    {
        if (empty($this->_token[$this->jwt_username])) {
            $client = new Client([
                'transport' => 'yii\httpclient\CurlTransport'
            ]);
            $request = $client->createRequest()
                ->setUrl($this->getBaseWpJson('jwt-auth/v1/token'))
                ->setMethod('post')
                ->setData([
                    'username' => $this->jwt_username,
                    'password' => $this->jwt_password,
                ]);

            $response = $request->send();
            if (!$response->isOk) {
                throw new Exception($response->statusCode . ' ' . $response->content);
            }

            $data = $response->getData();
            if (!empty($data['token'])) {
                $this->_token[$this->jwt_username] = $data['token'];
            }
        }

        return ArrayHelper::getValue($this->_token, [$this->jwt_username]);
    }

    /**
     * Get products
     */
    public function getProducts($page = 1, $perPage = 100, $sku = null, $ls_token, $ls_curlUrl)
    {
        return self::sendJwtRequest($ls_token, $ls_curlUrl, ['/wp-json/wc/v3/products', 'page' => $page, 'per_page' => $perPage, 'sku' => $sku, 'order' => 'asc']);
    }

    /**
     * Add product
     */
    public function addProduct($productData, $ls_token, $ls_curlUrl) 
    {
        return self::sendJwtRequest($ls_token, $ls_curlUrl, 'wc/v3/products/', 'post', Json::encode($productData));
    }

    /**
     * Update product
     */
    public function updateProduct($wooProductId, $productData, $ls_token, $ls_curlUrl) 
    {
        return self::sendJwtRequest($ls_token, $ls_curlUrl, 'wc/v3/products/' . $wooProductId . '/', 'put', Json::encode($productData));
    }

    /**
     * Update product
     */
    public function batchUpdateProductVariation($wooProductId, $productVariation, $ls_token, $ls_curlUrl) 
    {
        return self::sendJwtRequest($ls_token, $ls_curlUrl, 'wc/v3/products/' . $wooProductId . '/variations/batch', 'post', Json::encode($productVariation));
    }

    /**
     * Return woocommerce orders
     * @param string $status order status
     * @return array
     */
    public static function getOrders($params, $page = 1, $limit = 50, $ls_token, $ls_curlUrl)
    {
        array_unshift($params, 'wc/v3/orders');
        $params['page'] = $page;
        $params['per_page'] = $limit;
        $result = self::sendJwtRequest($ls_token, $ls_curlUrl, $params);

        return $result;
    }

    /**
     * Return woocommerce orders based on status
     * @param string $status order status
     * @param integer $page
     * @param integer $limit
     * @return array
     */
    public static function getOrdersByStatus($status = null, $page = 1, $limit = 50, $ls_token = '', $ls_curlUrl = '')
    {
        $params = [];
        if (!empty($status)) {
            $params['status'] = $status;
        }

        $result = self::getOrders($params, $page, $limit, $ls_token, $ls_curlUrl);
        return $result;
    }

    /**
     * Return woocommerce orders based on order ids
     * @param string $id order id
     * @param integer $page
     * @param integer $limit
     * @return array
     */
    public function getOrdersById($id, $page = 1, $limit = 50)
    {
        if (!is_array($id)) {
            $id = explode(',', $id);
        }
        $params = ['include' => $id];
        $result = $this->getOrders($params, $page, $limit);
        return $result;
    }
    
    /**
     * Return WPSL Stores
     * @param string $status order status
     * @return array
     */
    public function getWpslStores($status=null, $page = 1, $limit = 100)
    {
        $params = ['wp/v2/wpsl_stores'];
        if (!empty($status)) $params['status'] = $status;
        $params['page'] = $page;
        $params['per_page'] = $limit;
        $result = $this->sendJwtRequest($params);

        return $result;
    }

    /**
     * Add WPSL Stores
     */
    public function addWpslStores($customerData) 
    {
        return $this->sendJwtRequest('wp/v2/wpsl_stores', 'post', Json::encode($customerData));
    }
    
    /**
     * Update WPSL Stores
     */
    public function updateWpslStores($wpslId, $customerData) 
    {
        return $this->sendJwtRequest("wp/v2/wpsl_stores/$wpslId", 'post', Json::encode($customerData));
    }

    /**
     * Add WPSL Stores
     */
    public function deleteWpslStores($wpslId) 
    {
        return $this->sendJwtRequest("wp/v2/wpsl_stores/$wpslId?force=true", 'delete');
    }

    /**
     * 
     */
    public function shipmentTrackings($ls_curlUrl, $ls_token, $ls_wooOrderId) 
    {
        $params = array("0" => 'wc/v3/orders/' . $ls_wooOrderId . '/shipment-trackings');
        $result = self::sendJwtRequest($ls_token, $ls_curlUrl, $params);

        return $result;
    }

    /**
     * Return woo product variations
     * @return array
     */
    public function wooVariations($ls_wooProductId)
    {
        if ($ls_wooProductId) {
            $params = [];  
            $params['0'] = 'wc/v3/products/' . $this->wooProductId . '/variations';
            $params['per_page'] = $limit;          
            $la_wooVariations = self::sendJwtRequest($ls_token, $ls_curlUrl, $params);
            $la_wooVariations = ArrayHelper::index($la_wooVariations, 'sku');
            return $la_wooVariations;
        }
    }

    /**
     * Get images
     */
    public function getImages($ls_token, $ls_curlUrl, $parentId = null, $perPage = 100, $page = 1)
    {
        if (empty($parentId)) {
            return self::sendJwtRequest($ls_token, $ls_curlUrl, ['wp/v2/media', 'per_page' => $perPage, 'page' => $page]);
        }
        // return $this->sendJwtRequest(['wp/v2/media', 'parent' => $parentId, 'per_page' => $perPage, 'page' => $page]);
    }

    /**
     * Delete image
     */
    public function deleteImage($ls_token, $ls_curlUrl, $imageId) 
    {
        return self::sendJwtRequest($ls_token, $ls_curlUrl, ['wp/v2/media/' . $imageId, 'force' => true], 'delete');
    }
}
