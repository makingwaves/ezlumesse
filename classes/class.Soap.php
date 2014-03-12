<?php
namespace MakingWaves\eZLumesse;

/**
 * Class Soap
 * This class is unit tested by tests/class.SoapTest.php, so in case
 * of implementing new methods, please implement tests as well.
 * @package MakingWaves\eZLumesse\Tests
 */
class Soap
{
    /**
     * @var string
     */
    private $api_key = '';

    /**
     * @var string
     */
    private $api_endpoint = '';

    /**
     * @var string
     */
    private $username = '';

    /**
     * @var string
     */
    private $password = '';

    /**
     * @var string
     */
    private $namespace = '';

    /**
     * @var \SoapClient 
     */
    private $handler = null;

    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->loadSettings();
        $this->handler = $this->getConnectionHandler();
    }

    /**
     * Method makes a call to given SOAP function
     *
     * @param string $function_name
     * @param array $arguments
     * @return mixed
     * @throws SoapIncorrectFunctionNameException
     */
    public function call( $function_name, array $arguments = array() )
    {
        if ( !is_string( $function_name ) || strlen( $function_name ) <= 0 ) {
            throw new SoapIncorrectFunctionNameException( 'Function name needs to be a non empty string' );
        }
        
        return $this->handler->__soapCall(
            $function_name, $arguments
        );
    }

    /**
     * Loading required ini settings. Some of them (username, password) depends on environment
     *
     * @throws SoapMissingEnvironmentException
     */
    private function loadSettings()
    {
        $ini = \eZINI::instance( 'ezlumesse.ini' );
        $environment = $ini->variable( 'MainSettings', 'UseEnvironment' );
        if ( $environment === false ) {
            throw new SoapMissingEnvironmentException( 'Missing "UseEnvironment" value in INI settings' );
        }

        $this->api_key = $ini->variable( 'MainSettings', 'ApiKey' );
        $this->api_endpoint = $ini->variable( 'MainSettings', 'Endpoint' );
        $this->namespace = $ini->variable( 'MainSettings', 'Namespace' );
        $this->username = $ini->variable( $environment . '-Environment', 'UserName' );
        $this->password = $ini->variable( $environment . '-Environment', 'Password' );
    }

    /**
     * Method connects to the soap service and as a result returns SoapClient
     *
     * @return \SoapClient
     */
    private function getConnectionHandler()
    {
        $soapVar_Auth = new \SoapVar( array(
            'Username' => new \SoapVar( $this->username, XSD_STRING, null, $this->namespace, null, $this->namespace ),
            'Password' => new \SoapVar( $this->password, XSD_STRING, null, $this->namespace, null, $this->namespace )
        ), SOAP_ENC_OBJECT, null, $this->namespace, 'UsernameToken', $this->namespace );

        $soapVar_Auth_Token = new \SoapVar( array(
            'UsernameToken' => $soapVar_Auth
        ), SOAP_ENC_OBJECT, null, $this->namespace, 'UsernameToken', $this->namespace );

        $soapVar_Security = new \SoapVar( $soapVar_Auth_Token, SOAP_ENC_OBJECT, null, $this->namespace, 'Security', $this->namespace );
        $soapVar_Header = new \SoapHeader( $this->namespace, 'Security', $soapVar_Security, true, 'TlkPrincipal' );

        $handler = new \SoapClient( $this->api_endpoint . '?wsdl' );
        $handler->__setSoapHeaders( array( $soapVar_Header ) );
        $handler->__setLocation( $this->api_endpoint . '?api_key=' . $this->api_key );

        return $handler;
    }
}