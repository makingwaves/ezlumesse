<?php
namespace MakingWaves\eZLumesse\Tests;

class HandlerLogicTest extends EzLumesseTests
{
    /**
     * A name of class which is tested here
     * @var string
     */
    private $test_class = 'MakingWaves\eZLumesse\HandlerLogic';

    /**
     * Trying to get all ads, but correct credential are missing, so we should have an empty array here
     */
    public function testGetAllAds()
    {
        // apply ini settings, however they're incorrect at this point
        $this->setIniSettings();

        $object = new $this->test_class;
        $result = $object->getAllAds();

        $this->assertTrue( is_array( $result ) );
    }

    /**
     * @depends testGetAllAds
     */
    public function testGetAllAdsCount( $result )
    {
        $this->assertEquals( 0, sizeof( $result ) );
    }

    /**
     * Testing class constructor, which is responsible for setting 'soap' property
     */
    public function testConstuctor()
    {
        $property = new \ReflectionProperty( $this->test_class, 'soap' );
        $property->setAccessible( true );

        // apply ini settings
        $this->setIniSettings();

        $value = $property->getValue( new $this->test_class );
        $this->assertInstanceof( '\MakingWaves\eZLumesse\Soap', $value );
    }

    /**
     * @dataProvider providerSignedIntegersLessOrEqual100
     * @param int $input
     */
    public function testGetMaxResults( $input )
    {
        $method = new \ReflectionMethod( $this->test_class, 'getMaxResults' );
        $method->setAccessible( true );

        // apply ini settings
        $this->setIniSettings( true, array(
            'MainSettings' => array(
                'MaxResults' => $input
            )
        ) );

        $result = $method->invoke( new $this->test_class );
        $this->assertGreaterThan( 0, $result );
        $this->assertLessThanOrEqual( 100, $result );
    }

    /**
     * @dataProvider providerIntegerIncorrectOrOutOfRange
     * @expectedException \MakingWaves\eZLumesse\HandlerLogicIncorrectRangeException
     */
    public function testGetMaxResultsFail( $input )
    {
        $method = new \ReflectionMethod( $this->test_class, 'getMaxResults' );
        $method->setAccessible( true );

        // apply ini settings
        $this->setIniSettings( true, array(
            'MainSettings' => array(
                'MaxResults' => $input
            )
        ) );

        $method->invoke( new $this->test_class );
    }

    /**
     * @dataProvider providerNextPageCounterValues
     */
    public function testNextPageExistsTrue( $current_page, $items_per_page, $all_items )
    {
        $method = new \ReflectionMethod( $this->test_class, 'nextPageExists' );
        $method->setAccessible( true );

        // apply ini settings
        $this->setIniSettings();

        $this->assertTrue( $method->invoke( new $this->test_class, $current_page, $items_per_page, $all_items ) );
    }

    /**
     * @dataProvider providerThereIsNoNextPage
     */
    public function testNextPageExistsFalse( $current_page, $items_per_page, $all_items )
    {
        $method = new \ReflectionMethod( $this->test_class, 'nextPageExists' );
        $method->setAccessible( true );

        // apply ini settings
        $this->setIniSettings();

        $this->assertFalse( $method->invoke( new $this->test_class, $current_page, $items_per_page, $all_items ) );
    }

    /**
     * @dataProvider providerIncorrectIntegers
     * @expectedException \MakingWaves\eZLumesse\HandlerLogicIncorrectIntegersException
     */
    public function testNextPageExistsIncorrectAttributes( $current_page, $items_per_page, $all_items )
    {
        $method = new \ReflectionMethod( $this->test_class, 'nextPageExists' );
        $method->setAccessible( true );

        // apply ini settings
        $this->setIniSettings();

        $method->invoke( new $this->test_class, $current_page, $items_per_page, $all_items );
    }

    /**
     * @dataProvider providerCorrectIntegerSets
     */
    public function testCorrectInteger( $input )
    {
        $method = new \ReflectionMethod( $this->test_class, 'correctInteger' );
        $method->setAccessible( true );

        // apply ini settings
        $this->setIniSettings();

        $this->assertTrue( $method->invoke( new $this->test_class, $input ) );
    }

    /**
     * @dataProvider providerIncorrectIntegerSets
     */
    public function testCorrectIntegerFail( $input )
    {
        $method = new \ReflectionMethod( $this->test_class, 'correctInteger' );
        $method->setAccessible( true );

        // apply ini settings
        $this->setIniSettings();

        $this->assertFalse( $method->invoke( new $this->test_class, $input ) );
    }

    /**
     * Test that without correct credentials, getAdPage will return empty array
     */
    public function testGetAdPage()
    {
        $method = new \ReflectionMethod( $this->test_class, 'getAdPage' );
        $method->setAccessible( true );

        // apply ini settings
        $this->setIniSettings();

        $this->assertEquals( 0, sizeof( $method->invoke( new $this->test_class ) ) );
    }

    public function testGetProcessLengthNoObject()
    {
        // apply ini settings
        $this->setIniSettings();

        $object = new $this->test_class;
        $property = new \ReflectionProperty( $this->test_class, 'process_length' );
        $property->setAccessible( true );

        $result = $object->getProcessLength();
        $this->assertEquals( $property->getValue( $object ), $result );
    }

    /**
     * @expectedException \MakingWaves\eZLumesse\HandlerLogicTotalResultsMissingException
     */
    public function testGetProcessLengthEmptyObject()
    {
        // apply ini settings
        $this->setIniSettings();

        $object = new $this->test_class;
        $object->getProcessLEngth( new \stdClass() );
    }
}