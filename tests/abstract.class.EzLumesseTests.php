<?php
namespace MakingWaves\eZLumesse\Tests;

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
}