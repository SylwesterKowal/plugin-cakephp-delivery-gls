<?php
namespace Gls\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;

/**
 * Gls component
 */
class GlsComponent extends Component
{
    const _SOAP_URL  = 'https://xxx.xxxxxxxx.xx/ade_webapi2.php?wsdl';

    private $username;
    private $password;
    private $session;
    private $client;

    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [];

    public function connect()
    {
        $this->client = new SoapClient( _SOAP_URL );
        // for PHP client XML single element (array) interpretation problem use this option: 'features' => SOAP_SINGLE_ELEMENT_ARRAYS
        // for debug:
        // $hClient = new SoapClient( 'https://xxx.xxxxxxxx.xx/ade_webapi2.php?wsdl', array( 'trace' => TRUE, 'cache_wsdl' => WSDL_CACHE_NONE ) );
        try {

            $oCredit = new stdClass();
            $oCredit->user_name = $this->username;
            $oCredit->user_password = $this->password;
            // array style (alternative)
            // $oCredit = array( 'user_name' => 'my_user', 'user_password' => 'my_password;' );
            $oClient = $this->client->adeLogin( $oCredit );
            $this->session = $oClient->return->session;

        } catch ( SoapFault $fault ) {

            println( 'Code: ' . $fault->faultcode . ', FaultString: ' . $fault->faultstring );
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
        $oSess = new stdClass();
        $oSess->session = $this->session;
        $oClient = $this->client->adeLogout( $oSess );
    }
}
