<?php
namespace MakingWaves\eZLumesse\Tests;

/**
 * Unit Testing configuration class
 */
class EzLumesseTestSuite extends \ezpDatabaseTestSuite
{
    public function __construct()
    {
        parent::__construct();
        $this->insertDefaultData = false;
        $this->setName( 'eZLumesse extension test suite' );

        // Adding tests
        $this->addTestSuite( 'MakingWaves\eZLumesse\Tests\SoapTest' );
    }

    public static function suite()
    {
        return new self();
    }
}