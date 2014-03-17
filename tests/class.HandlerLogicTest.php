<?php
namespace MakingWaves\eZLumesse\Tests;

class HandlerLogicTest extends EzLumesseTests
{
    /**
     * A name of class which is tested here
     * @var string
     */
    protected $test_class = 'MakingWaves\eZLumesse\HandlerLogic';

    /**
     * Trying to get all ads, but correct credential are missing, so we should have an empty array here
     */
    public function testGetAllAds()
    {
        // apply ini settings, however they're incorrect at this point
        $this->setIniSettings();

        $object = new $this->test_class( $this->getOptions() );
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

        $value = $property->getValue( new $this->test_class( $this->getOptions() ) );
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

        $result = $method->invoke( new $this->test_class( $this->getOptions() ) );
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

        $method->invoke( new $this->test_class( $this->getOptions() ) );
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

        $this->assertTrue( $method->invoke( new $this->test_class( $this->getOptions() ), $current_page, $items_per_page, $all_items ) );
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

        $this->assertFalse( $method->invoke( new $this->test_class( $this->getOptions() ), $current_page, $items_per_page, $all_items ) );
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

        $method->invoke( new $this->test_class( $this->getOptions() ), $current_page, $items_per_page, $all_items );
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

        $this->assertTrue( $method->invoke( new $this->test_class( $this->getOptions() ), $input ) );
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

        $this->assertFalse( $method->invoke( new $this->test_class( $this->getOptions() ), $input ) );
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

        $this->assertEquals( 0, sizeof( $method->invoke( new $this->test_class( $this->getOptions() ) ) ) );
    }

    public function testGetProcessLengthNoObject()
    {
        // apply ini settings
        $this->setIniSettings();

        $object = new $this->test_class( $this->getOptions() );
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

        $object = new $this->test_class( $this->getOptions() );
        $object->getProcessLEngth( new \stdClass() );
    }

    /**
     * Test for option language getter
     */
    public function testGetLanguage()
    {
        // apply ini settings
        $this->setIniSettings();
        $object = new $this->test_class( $this->getOptions() );

        $method = new \ReflectionMethod( $this->test_class, 'getLanguage' );
        $method->setAccessible( true );

        $property = new \ReflectionProperty( $this->test_class, 'lang' );
        $property->setAccessible( true );

        $this->assertEquals( $property->getValue( $object ), $method->invoke( $object ) );
    }

    /**
     * @dataProvider providerCorrectLanguageCodeDefinedInIni
     */
    public function testGetLumesseLanguage( $lang_code )
    {
        $result = $this->callPrivateMethod( 'getLumesseLanguage', array(
            $lang_code
        ) );

        $this->assertGreaterThan( 0, strlen( $result ) );
    }

    /**
     * @dataProvider providerIncorrectLanguageCode
     * @expectedException \MakingWaves\eZLumesse\HandlerLogicIncorrectLanguageCodeException
     */
    public function testGetLumesseLanguageIncorrect( $lang_code )
    {
        $this->callPrivateMethod( 'getLumesseLanguage', array(
            $lang_code
        ) );
    }

    /**
     * @dataProvider providerIncorrectStringType
     * @expectedException \MakingWaves\eZLumesse\HandlerLogicIncorrectXmlStringException
     */
    public function testStringToXmlblockIncorrectString( $string )
    {
        $this->callPrivateMethod( 'stringToXmlblock', array(
            $string,
            1
        ) );
    }

    /**
     * @dataProvider providerIncorrectObjectId
     * @expectedException \MakingWaves\eZLumesse\HandlerLogicIncorrectObjectIdException
     */
    public function testStringtoXmlblockIncorrectObjectId( $id )
    {
        $this->callPrivateMethod( 'stringToXmlblock', array(
            'test',
            $id
        ) );
    }

    /**
     * @dataProvider providerCorrectDateString
     */
    public function testDateToTimestamp( $date_time )
    {
        $result = $this->callPrivateMethod( 'dateToTimestamp', array(
            $date_time
        ) );

        $this->assertGreaterThan( 0, filter_var( $result, FILTER_VALIDATE_INT ) );
        $this->assertGreaterThan( 0, strlen( $result ) );
    }

    /**
     * @expectedException \MakingWaves\eZLumesse\HandlerLogicIncorrectDateFormatException
     * @dataProvider providerIncorrectDateString
     */
    public function testDateToTimestampIncorrectFormat( $date_time )
    {
        $this->callPrivateMethod( 'dateToTimestamp', array(
            $date_time
        ) );
    }

    /**
     * @expectedException \MakingWaves\eZLumesse\HandlerLogicIncorrectTimestampException
     * @dataProvider providerIncorrectStringForTimestamp
     */
    public function testDateToTimestampIncorrectTimestamp( $date_time )
    {
        $this->callPrivateMethod( 'dateToTimestamp', array(
            $date_time
        ) );
    }

    /**
     * @dataProvider providerIncorrectStringType
     * @expectedException \MakingWaves\eZLumesse\HandlerLogicIncorrectLovIdentifierException
     */
    public function testGetStandardLovIncorrectIdentifier( $incorrect_string )
    {
        $this->callPrivateMethod( 'getStandardLov', array(
            new \stdClass(),
            $incorrect_string
        ) );
    }

    /**
     * @expectedException \MakingWaves\eZLumesse\HandlerLogicLovDoesNotExistException
     */
    public function testGetStandardLovIncorrectLov()
    {
        $this->callPrivateMethod( 'getStandardLov', array(
            new \stdClass(),
            'some_string'
        ) );
    }

    public function testFetchAllPublishedAds()
    {
        $options = new \ReflectionProperty( $this->test_class, 'options' );
        $options->setAccessible( true );

        // apply ini settings
        $this->setIniSettings();
        $object = new $this->test_class( $this->getOptions( array(
            'parent_node' => 1
        ) ) );

        $method = new \ReflectionMethod( $this->test_class, 'FetchAllPublishedAds' );
        $method->setAccessible( true );
        $result = $method->invoke( $object );

        $this->assertTrue( is_array( $result ) );

        return $object;
    }
}