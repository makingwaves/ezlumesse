<?php
namespace MakingWaves\eZLumesse\Tests;

/**
 * Class SoapTest
 * Class contains tests for Soap class. Can be run by:
 * $ php tests/runtests.php --dsn mysql://root:pass@localhost/db_name --filter="SoapTest" --db-per-test
 * @package MakingWaves\eZLumesse\Tests
 */
class SoapTest extends EzLumesseTests
{
    /**
     * A name of class which is tested here
     * @var string
     */
    private $test_class = 'MakingWaves\eZLumesse\Soap';

    /**
     * Test correct connection handler
     */
    public function testGetConnectionHandler()
    {
        $method = new \ReflectionMethod( $this->test_class, 'getConnectionHandler' );
        $method->setAccessible( true );

        // apply ini settings
        $this->setIniSettings();

        $result = $method->invoke( new $this->test_class );
        $this->assertInstanceof( '\SoapClient', $result );
    }

    /**
     * @expectedException \MakingWaves\eZLumesse\SoapMissingEnvironmentException
     */
    public function testLoadSettingsMissingEnvironment()
    {
        $method = new \ReflectionMethod( $this->test_class, 'loadSettings' );
        $method->setAccessible( true );

        // run the method
        $method->invoke( new $this->test_class );

        return $method;
    }


    /**
     * @dataProvider providerSoapConfigurationProperties
     */
    public function testLoadSettings( $property_name )
    {
        $method = new \ReflectionMethod( $this->test_class, 'loadSettings' );
        $method->setAccessible( true );

        // apply ini settings
        $this->setIniSettings();

        // run the method
        $method->invoke( new $this->test_class );

        // check property value
        $property = new \ReflectionProperty( $this->test_class, $property_name );
        $property->setAccessible( true );

        $this->assertNotEmpty( $property->getValue( new $this->test_class ) );
    }

    /**
     * @dataProvider providerEmptyOrIncorrectString
     * @expectedException \MakingWaves\eZLumesse\SoapIncorrectFunctionNameException
     */
    public function testCallIncorrectFunction( $input )
    {
        // apply ini settings
        $this->setIniSettings();

        $object = new $this->test_class;
        $object->call( $input, 'correct string' );
    }

    /**
     * @expectedException \MakingWaves\eZLumesse\SoapIncorrectArgumentsException
     * @dataProvider providerIncorrectStringType
     */
    public function testCallIncorrectArguments( $input )
    {
        // apply ini settings
        $this->setIniSettings();

        $object = new $this->test_class;
        $object->call( 'correct string', $input );
    }
}
