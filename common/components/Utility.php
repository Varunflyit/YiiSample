<?php

namespace app\common\components;

use Yii;
use yii\base\Component;
use Aws\S3\S3Client;
use yii\httpclient\Client;
use yii\httpclient\Exception;
use yii\base\InvalidArgumentException;
use yii\helpers\Json;
use yii\helpers\ArrayHelper;

class Utility extends Component {

    public static function getCommonBehaviors() {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $behaviors;
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::className(),
            'cors' => [
                'Origin' => \Yii::$app->params['crossOriginAllowedDomains'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => false,
                'Access-Control-Max-Age' => 3600, // Cache (seconds)
            ],
        ];

        return $behaviors;
    }
    

    public static function filterInput($ls_str) {
        $ls_str = addslashes(trim($ls_str));
        //  $ls_str = self::removeBadString($ls_str);
        return $ls_str;
    }

    public static function filterInputArray($value) {
        $value = is_array($value) ? array_map('self::filterInputArray', $value) : self::filterInput($value);
        return $value;
    }

    public static function filterOutput($ls_str) {
        $ls_str = stripslashes(trim($ls_str));
        return $ls_str;
    }

    public static function filterOutputArray($value) {
        $value = is_array($value) ? array_map('self::filterOutputArray', $value) : self::filterOutput($value);
        return $value;
    }

    Public function removeBadString($ls_value) {
        // Stripslashes   
        if (get_magic_quotes_gpc()) {
            $ls_value = stripslashes($ls_value);
        } // Quote if not a number
        if (!is_numeric($ls_value)) {
            $ls_value = "'" . addslashes($ls_value) . "'";
        }
        $la_badString = array("select", "drop", ";", "--", "insert", "delete", "xp_", "%20union%20", "/*", "*/union/*", "+union+", "load_file", "outfile", "document.cookie", "onmouse", "<script", "<iframe", "<applet", "<meta", "<style", "<form", "<img", "<body", "<link", "_GLOBALS", "_REQUEST", "_GET", "_POST", "include_path", "prefix", "http://", "https://", "ftp://", "smb://");
        for ($i = 0; $i < count($la_badString); $i++) {
            $ls_value = str_replace($la_badString [$i], "", $ls_value);
        }

        return $ls_value;
    }

    public static function responseSuccess($result = [], $message = 'success', $resultInfo = []) {
        Yii::$app->response->statusCode = 200;

        if (count($resultInfo) > 0) {
            $result['result_info'] = $resultInfo;
        }

        return array('result' => $result, 'success' => true, 'message' => $message);
    }

    public static function responseError($data = [], $message = 'failed', $statusCode = 404) {
        Yii::$app->response->statusCode = $statusCode;
        
        return array('success' => false, 'errors' => $data);
    }


    public static function generateDynamicEmailContent($ls_text, $result = array()) {
        $ls_text = str_replace("[WEBSITE_LINK]", Yii::$app->params['website'], $ls_text);
        foreach ($result as $key => $val) {
            $ls_customizedVarName = "[" . $key . "]";
            $ls_text = str_replace($ls_customizedVarName, $val, $ls_text);
        }
        return $ls_text;
    }


    public static function getFinalResponce($la_result) {

        if (isset($la_result['data']))
            return Utility::responseSuccess($la_result['data']);
        else if (isset($la_result['errors']))
            return Utility::responseError($la_result['errors']);
        else if (isset($la_result['error']))
            return Utility::responseError([], $la_result['error']);
        else
            return Utility::responseError();
    }



    public function setMailHeader() {
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html;charset=UTF-8\r\n";
        return $headers;
    }


    public static function get_client_ip() {
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        } else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        } else if (isset($_SERVER['HTTP_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        } else if (isset($_SERVER['REMOTE_ADDR'])) {
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        } else
            $ipaddress = '';

        $ip = explode(',', $ipaddress)[0];

        return $ip;
    }

    public static function create_camel_case_to_snake_case($ls_value) {
        return strtolower(preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', $ls_value));
    }

    public static function fetCompanyIdFromUrl() {
        $companyId = Yii::$app->getRequest()->getQueryParam('companyId');
        $role = \app\models\User::findUserRole(Yii::$app->user->identity->id);       
        if($role=='superAdmin' || $role=='admin'){
           if(\app\models\Companies::find()->where(['company_id' => $companyId,'is_active' => 'Y'])->exists()){
                return $companyId;
            } else {
                return Utility::responseError(
                    [
                        'code' => 'company_not_found',
                        'message' => \Yii::t('app', 'company_not_found')
                    ],
                    '',
                    400
                );
            }
        }else{
            if (\app\models\UsersCompaniesMap::find()->where(['user_id' => Yii::$app->user->identity->id,"company_id"=>$companyId])->exists()){
                return $companyId;
            } else {
                return Utility::responseError(
                    [
                        'code' => 'company_not_found',
                        'message' => \Yii::t('app', 'company_not_found')
                    ],
                    '',
                    400
                );
            }
        }            
    }

    public static function fetIdFromUrl($url, $position) {
        $explodedUrl = explode("/",trim($url));
        return $explodedUrl[$position];
    }

    public static function curlInit($ls_curlUrl, $la_headerArr = [], $la_post = [], $ls_customRequest){
        $error_msg = '';
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $ls_curlUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $ls_customRequest,
            CURLOPT_POSTFIELDS => json_encode($la_post),
            CURLOPT_HTTPHEADER => $la_headerArr,

            CURLOPT_FAILONERROR => true // for showing error from URL
        ));
        $response = curl_exec($curl);
        if (curl_errno($curl)) {
            $error_msg = curl_error($curl); // catch the errors from URL
        }
        curl_close($curl);
        if ($error_msg != '') {
            return $error_msg; // return the error if exist
        }
        return json_decode($response,true);
    }

    public static function findKey($array, $keySearch) {
        foreach ($array as $key => $item) {
            if ($key == $keySearch) {
                return true;
            } 
            elseif (is_array($item) && self::findKey($item, $keySearch)) {
                return true;
            }
        }
        return false;
    }

    public static function uploadFileToAmazonServer($fileType = 'application/json', $tempPath)
    {
        $params = Yii::$app->params['amazon_s3'];
        $s3_base_path = Yii::$app->params['s3_base_path'];
        $ls_fileName = time().'.json';
        if($tempPath){
            $s3 = new S3Client([
                'region' => $params['region'],
                'version' => 'latest',
                'credentials' => [
                    'key' => $params['key'],
                    'secret' => $params['secret'],
                ],
            ]);
            $result = $s3->putObject([
                'Bucket' => $params['Bucket'],
                'Key' => $s3_base_path . "/temp/" . $ls_fileName,
                'ContentType' => $fileType,
                'SourceFile' => $tempPath,
                'ACL' => 'public-read',
            ]);
            if ($result['@metadata']['statusCode'] == 200) {
                return "temp/" . $ls_fileName;
            } else {
                return Utility::responseError(
                            [
                                'code' => 'file_not_uploaded',
                                'message' => \Yii::t('app', 'file_not_uploaded')
                            ],
                        );
            }
        } else{
            return Utility::responseError(
                            [
                                'code' => 'choose_a_file',
                                'message' => \Yii::t('app', 'choose_a_file')
                            ],
                        );
        }
    }

    public static function httpClientCurlInit($ls_curlUrl, $ls_method = 'get', $la_action = null, $ls_netapi_username = null, $ls_netapi_key = null, $ls_access_key = null, $ls_secret_key = null, $data = null)
    {
        $client = new Client([
            'transport' => 'yii\httpclient\CurlTransport'
        ]);
        $request = $client->createRequest()
            ->addHeaders([
                'X_ACCESS_KEY' => $ls_access_key,
                'X_SECRET_KEY' => $ls_secret_key,
                'NETOAPI_ACTION' => $la_action,
                // 'NETOAPI_USERNAME' => $ls_netapi_username,
                // 'NETOAPI_KEY' => $ls_netapi_key,
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ])
            ->setMethod($ls_method)
            ->setUrl($ls_curlUrl);
            if (is_array($data)) {
                $request->setData($data);
            } elseif (is_string($data)) {
                $request->setContent($data);
            }
        // var_dump($request->getMethod(), $request->getHeaders());
        $response = $request->send();
        if($ls_secret_key){
        // var_dump($request->getFullUrl(), $request->getHeaders());
        }

        return $response->getData();
    }

    public static function httpClientCurlInitNew($la_curlUrl, $ls_method = 'get', $la_headerData = [], $data = null, $debug = false)
    {
        $la_headerData = array_merge($la_headerData,['Accept'=> 'application/json','Content-Type'  => 'application/json']);

        $client = new Client([
            'transport' => 'yii\httpclient\CurlTransport'
        ]);
        $request = $client->createRequest()
            ->addHeaders($la_headerData)
            ->setMethod($ls_method)
            ->setUrl($la_curlUrl);
        if (is_array($data)) {
            $request->setData($data);
        } elseif (is_string($data)) {
            $request->setContent($data);
        }
        if ($debug) {
            var_dump($request->getFullUrl(),$request->getMethod(), $request->getHeaders(), $request->getData(), $request->getContent());
            exit;
        }
        $response = $request->send();

        $la_response = $response->getData();

        // if (array_key_exists('Ack', $la_response) && $la_response['Ack'] == 'Error') {
            
        //     return $la_response['Messages'];
        // }

        return $la_response;
    }

    public static function curlInitForBasicAuth($ls_curlUrl, $ls_authDetails, $ls_method = 'get', $la_headerData = [], $data = null, $debug = false){
        $error_msg = '';
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $ls_curlUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $ls_method,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => $la_headerData,
            CURLOPT_USERPWD => $ls_authDetails, // $username . ":" . $password

            CURLOPT_FAILONERROR => true // for showing error from URL
        ));

        if($debug) {
            print_r(['url' => $ls_curlUrl, 'auth_details' => $ls_authDetails, 'method' => $ls_method, 'header_data' => $la_headerData, 'payload' => $data]);
            print_r(curl_getinfo($curl));
            exit;
        }

        $response = curl_exec($curl);
        if (curl_errno($curl)) {
            $error_msg = curl_error($curl); // catch the errors from URL
        }
        curl_close($curl);
        if ($error_msg != '') {
            return $error_msg; // return the error if exist
        }
        return json_decode($response,true);
    }

    public static function stringSnakeToCamelCase($ls_data)
    {
        return str_replace("_", "", ucwords($ls_data, " /_"));
    }
}

?>