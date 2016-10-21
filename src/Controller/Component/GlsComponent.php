<?php
namespace Gls\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;

/**
 * Gls component
 */
class GlsComponent extends Component
{

    private $username;
    private $password;
    private $session;
    private $hClient;
    private $store;
    private $host;
    private $orderId;
    private $deliveryId;
    private $percelNumber;

    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = ['https://adeplus.gls-poland.com/adeplus/pm1/ade_webapi2.php?wsdl'];

    public function init($data)
    {
        $this->loadModel('Stores');

        $this->orderId = $data['order_id'];
        $this->deliveryId = $data['delivery_id'];

        $this->setSore($data);
        return $this;
    }

    public function connect()
    {
        // for PHP client XML single element (array) interpretation problem use this option: 'features' => SOAP_SINGLE_ELEMENT_ARRAYS
        // for debug:
        // $hClient = new SoapClient( 'https://xxx.xxxxxxxx.xx/ade_webapi2.php?wsdl', array( 'trace' => TRUE, 'cache_wsdl' => WSDL_CACHE_NONE ) );
        try {
            $this->hClient = new \SoapClient($this->store->api_url);
            $oCredit = new \stdClass();
            $oCredit->username = $this->store->api_username;
            $oCredit->password = $this->store->api_password;

            $oClient = $this->hClient->adeLogin($oCredit);
            $this->session = $oClient->return->session;

            return $this;

        } catch (\SoapFault $fault) {

            debug('Code: ' . $fault->faultcode . ', FaultString: ' . $fault->faultstring);
            throw new Exception('Invalid connection');
            /* for debug:
            echo '<h2>Request</h2>';
            echo '<pre>' . $hClient->__getLastRequestHeaders() . '</pre>';
            echo '<pre>' . htmlspecialchars( $hClient->__getLastRequest(), ENT_QUOTES ) . '</pre>';
            echo '<h2>Response</h2>';
            echo '<pre>' . $hClient->__getLastResponseHeaders() . '</pre>';
            echo '<pre>' . htmlspecialchars( $hClient->__getLastResponse(), ENT_QUOTES ) . '</pre>';
            */
        }
    }

    /**
     * Odczytuje numery przewozowe dla zamówienia
     * @return $this
     */
    public function checkParcelIsSetForOrder()
    {
        $pickupIDs = $this->getPickupIDs();
        if (is_array($pickupIDs)) {
            foreach ($pickupIDs as $kePid => $pickapId) {
                $_percelNumber = $this->getParcelID($pickapId);
                if ($_percelNumber) {
                    $this->percelNumber[] = $_percelNumber;
                }
            }
        }
        return $this;
    }

    /**
     * Jeśli odczytane zostaną numery zadanie jest zamykane jesli nie czeka na kolejne runworker;
     * @return bool
     */
    public function saveParcelInStore()
    {
        return $this->sendTrackParcelNumber();

    }

    private function getDeliveryData($data)
    {
        return $this->Deliveries->find()
            ->where([
                'Deliveries.id' => $this->deliveryId,
                'Deliveries.order_id' => $this->orderId])
            ->contain('Stores')
            ->first();
    }

    private function setSore($delivery)
    {
        $delivery = $this->getDeliveryData($delivery);
        $this->store = $this->Stores->find()
            ->where(['code' => $delivery->code, 'curier_id' => self::GLS_ID])
            ->first();
        $this->host = $delivery->host;
    }

    private function sendTrackParcelNumber()
    {
        if (is_array($this->percelNumber) && count($this->percelNumber >= 1)) {
            $order_data = [
                'orderId' => $this->orderId,
                'code' => $this->store->code,
                'title' => 'GLS',
                'number' => implode(', ', $this->percelNumber)
            ];

            $page = '/ordersgrid/index/index';
            $url = 'http://' . $this->host . $page;
            $plaintext = serialize($order_data);
            $key = md5($this->store->code, true);
            $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
            $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
            $ciphertext = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $plaintext, MCRYPT_MODE_CBC, $iv);
            $ciphertext = $iv . $ciphertext;
            $ciphertext_base64 = base64_encode($ciphertext);


            $data['data'] = $ciphertext_base64;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
            $code = curl_exec($ch);
            curl_close($ch);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Generuje numer Nadania Paczki i Zamyka zlecenie w GLS
     *
     * @param $cSess
     * @param $hClient
     * @return mixed
     */
    private function createPickUp($cSess, $hClient)
    {

        try {

            $oInput = new \stdClass();
            $oInput->session = $cSess;
            $oInput->consigns_ids = new \stdClass();
            $oInput->consigns_ids->items[] = $this->delivery_id;
            $oInput->desc = $this->recivesData['ID'];
            $oClient = $hClient->adePickup_Create($oInput);
            return $oClient->return->id;

        } catch (\SoapFault $fault) {

            println('Code: ' . $fault->faultcode . ', FaultString: ' . $fault->faultstring);
            /* for debug:
            echo '<h2>Request</h2>';
            echo '<pre>' . $hClient->__getLastRequestHeaders() . '</pre>';
            echo '<pre>' . htmlspecialchars( $hClient->__getLastRequest(), ENT_QUOTES ) . '</pre>';
            echo '<h2>Response</h2>';
            echo '<pre>' . $hClient->__getLastResponseHeaders() . '</pre>';
            echo '<pre>' . htmlspecialchars( $hClient->__getLastResponse(), ENT_QUOTES ) . '</pre>';
            */

        }
    }

    /**
     * Odczytuje
     *
     * @param $idNadania
     * @return bool|string
     */
    private function getParcelID($idNadania)
    {
        $oInput = new \stdClass();
        $oInput->session = $this->session;
        $oInput->id = $idNadania;
        $oInput->id_start = 0;
        $oPaczki = $this->hClient->adePickup_GetConsignIDs($oInput);

        $percelNumber = false;
        if (is_array($oPaczki->return->items)) {
            foreach ($oPaczki->return->items as $ppkeys => $paczki) {
                $oInput = new \stdClass();
                $oInput->session = $this->session;
                $oInput->id = $paczki;
                $oPaczka = $this->hClient->adePickup_GetConsign($oInput);
                debug($oPaczka);
                if (is_array($oPaczka->return->parcels->items)) {
                    $percelNumbers = [];
                    foreach ($oPaczka->return->parcels->items as $ipkey => $parcel) {
                        if ($this->orderId == $parcel->reference) {
                            $percelNumbers[] = $parcel->number;
                        }
                    }
                    $percelNumber = implode(',', $percelNumbers);
                } else {
                    if ($this->orderId == $oPaczka->return->parcels->items->reference) {
                        $percelNumber = $oPaczka->return->parcels->items->number;
                    }
                }
            }
        } else {
            $oInput = new \stdClass();
            $oInput->session = $this->session;
            $oInput->id = $oPaczki->return->items;
            $oPaczka = $this->hClient->adePickup_GetConsign($oInput);
            if (is_array($oPaczka->return->parcels->items)) {
                $percelNumbers = [];
                foreach ($oPaczka->return->parcels->items as $ipkey => $parcel) {
                    if ($this->orderId == $parcel->reference) {
                        $percelNumbers[] = $parcel->number;
                    }
                }
                $percelNumber = implode(',', $percelNumbers);
            } else {
                if ($this->orderId == $oPaczka->return->parcels->items->reference) {
                    $percelNumber = $oPaczka->return->parcels->items->number;
                }
            }
        }
        return $percelNumber;
    }

    private function getPickupIDs()
    {
        try {

            $oInput = new \stdClass();
            $oInput->session = $this->session;
            $oInput->id_start = 0;
            $oClient = $this->hClient->adePickup_GetIDs($oInput);

            debug($oClient);
            // stdClass Object ( [return] => stdClass Object ( [items] => Array ( [0] => 377 [1] => 376 [2] => 375 [3] => 374 [4] => 373 [5] => 372 [6] => 371 [7] => 370 ) ) )

            return $oClient->return->items;
        } catch (\SoapFault $fault) {

            debug('Code: ' . $fault->faultcode . ', FaultString: ' . $fault->faultstring);
            return false;

            /* for debug:
            echo '<h2>Request</h2>';
            echo '<pre>' . $hClient->__getLastRequestHeaders() . '</pre>';
            echo '<pre>' . htmlspecialchars( $hClient->__getLastRequest(), ENT_QUOTES ) . '</pre>';
            echo '<h2>Response</h2>';
            echo '<pre>' . $hClient->__getLastResponseHeaders() . '</pre>';
            echo '<pre>' . htmlspecialchars( $hClient->__getLastResponse(), ENT_QUOTES ) . '</pre>';
            */

        }
    }

    public function disconect()
    {
        $oSess = new \stdClass();
        $oSess->session = $this->session;
        $oClient = $this->hClient->adeLogout($oSess);
    }
}
