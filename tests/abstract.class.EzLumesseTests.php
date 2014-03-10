<?php
namespace MakingWaves\eZLumesse\Tests;

/**
 * Parent class for all test classes inside eZLumesse extension
 */
abstract class EzLumesseTests extends \ezpDatabaseTestCase
{
    /**
     * Method sets the ini settings
     * @param bool $load_original
     * @param array $custom_settings
     */
    protected function setIniSettings( $load_original = true, $custom_settings = array() )
    {
        if ( $load_original === true )
        {
            $original_ini = \eZIni::instance( 'ezlumesse.ini', 'extension/ezlumesse/settings' );
            foreach ( $original_ini->groups() as $group_name => $group )
            {
                foreach ( $group as $var_name => $var_data )
                {
                    \ezpINIHelper::setINISetting( 'ezlumesse.ini', $group_name, $var_name, $var_data );
                }
            }
        }

        foreach ( $custom_settings as $group_name => $group )
        {
            foreach ( $group as $var_name => $var_data )
            {
                \ezpINIHelper::setINISetting( 'ezlumesse.ini', $group_name, $var_name, $var_data );
            }
        }
    }

    /**
     * Method is fired when exiting the test
     */
    public function tearDown()
    {
        \ezpINIHelper::restoreINISettings();
        parent::tearDown();
    }

    /**
     * Returns a list of properties which are used for soap configuration
     * @return array
     */
    public function providerSoapConfigurationProperties()
    {
        return array(
            array( 'api_key' ), array( 'api_endpoint' ), array( 'username' ), array( 'password' ), array( 'namespace' )
        );
    }

    /**
     * Returns the elements which are incorrect strings
     * @return array
     */
    public function providerIncorrectStringType()
    {
        return array(
            array( 1 ), array( 0 ), array( -1 ), array( null ), array( true ), array( false ), array( array() ), array( 1.3 )
        );
    }

    /**
     * Returns the elements which are empty or incorrect strings
     * @return array
     */
    public function providerEmptyOrIncorrectString()
    {
        return array_merge(
            $this->providerIncorrectStringType(), array( array( '' ) )
        );
    }

    /**
     * Returns the signed integers which are less or equal 100
     * @return array
     */
    public function providerSignedIntegersLessOrEqual100()
    {
        return array(
            array( 1 ), array( '1' ), array( 100 ), array( '100' )
        );
    }

    /**
     * Returns the elements which are not integers or they are correct integers, but out of range 0-100
     * @return array
     */
    public function providerIntegerIncorrectOrOutOfRange()
    {
        return array(
            array( 0 ), array( '0' ), array( -10 ), array( 10.5 ), array( 'test' ), array( 101 ), array( null ), array( array() )
        );
    }

    /**
     * Returns a set of correct values defined as attributes of function getNextPage.
     * As a result of those values we should get "true"
     * @return array
     */
    public function providerNextPageCounterValues()
    {
        return array(
            array( 1, 100, 110 ), array( 3, 50, 151 ), array( 4, 100 ,554 )
        );
    }

    /**
     * Returns a set of correct values defined as attributes of function getNextPage.
     * As a result of those values we should get "false"
     * @return array
     */
    public function providerThereIsNoNextPage()
    {
        return array(
            array( 100, 100, 90 ), array( 200, 100, 150 ), array( 150, 50, 150 )
        );
    }

    /**
     * Returns a set of values, where one of the value is not correct signed integer
     * @return array
     */
    public function providerIncorrectIntegerInSet()
    {
        return array(
            array( -1, 1, '2' ) , array( 1, 2, 'test' ), array( 0, 0, 0 ), array( 1, 2, null ), array( 3, 1.5, 5 )
        );
    }

    /**
     * Returns correct integers or arrays containing correct integers
     * @return array
     */
    public function providerCorrectIntegerSets()
    {
        return array(
            array( 1 ), array( '2' ), array( array( 1, 2 ) ), array( array( '1', '2' ) ), array( array( 'test' => 1, 'key' => '2' ) )
        );
    }

    /**
     * Returns incorrect integers or arrays containing at least one incorrect integer
     * @return array
     */
    public function providerIncorrectIntegerSets()
    {
        return array(
            array( -1 ), array( 1.2 ), array( 'test' ), array( array( 1, 'test' ) )
        );
    }
}