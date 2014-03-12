<?php
namespace MakingWaves\eZLumesse;

class HandlerLogic
{
    /**
     * @var Soap
     */
    private $soap;

    /**
     * @var int
     */
    private $max_results = null;

    /**
     * @var int
     */
    private $process_length = 0;

    /**
     * @var array
     */
    private $data = array();

    /**
     * @var \SQLIImportHandlerOptions
     */
    private $options;

    /**
     * @var \eZDB
     */
    private $db;

    /**
     * @var string
     */
    private $lang = 'nor-NO';

    /**
     * Default constructor
     */
    public function __construct( \SQLIImportHandlerOptions $options )
    {
        $this->soap = new Soap();
        $this->options = $options;
        $this->db = \eZDB::instance();
        $this->lang = $this->getLanguage();
    }

    private function addNewObject( \stdClass $row )
    {
        $this->db->begin();

        $object = \SQLIContent::create( new \SQLIContentOptions( array(
            'class_identifier' => 'lumesse_offer',
            'language' => $this->lang
        ) ) );
//
//        $date_time = explode( 'T', $row->postingStartDate );
//        print '<pre>';
//        var_dump(strtotime( $date_time[0] ));
//        print '</pre>';die;

        $object->fields[$this->lang]->name = $row->jobTitle;
        $object->fields[$this->lang]->url = $row->applicationUrl;
        $object->fields[$this->lang]->company_info = $this->stringToXmlblock( $row->customFields->customField[0]->value, $object->attribute( 'id' ) );
        $object->fields[$this->lang]->job_info = $this->stringToXmlblock( $row->customFields->customField[1]->value, $object->attribute( 'id' ) );
        $object->fields[$this->lang]->commence = '1221672010';

        $folder_publisher = \SQLIContentPublisher::getInstance();
        $folder_publisher->setOptions( new \SQLIContentPublishOptions( array(
            'parent_node_id' => $this->options->attribute( 'parent_node' )
        ) ) );

        $folder_publisher->publish( $object );

        $this->db->commit();
    }

    /**
     * Method validates whether given input contains a correct positive integer values
     * @param array|int|string $input
     * @return bool
     */
    private function correctInteger( $input )
    {
        if ( is_array( $input ) ) {
            foreach( $input as $item ) {
                if ( ( !is_int( $item ) && !filter_var( $item, FILTER_VALIDATE_INT ) ) || $item < 0 ) {
                    return false;
                }
            }

            return true;
        }

        return ( ( !is_int( $input ) && !filter_var( $input, FILTER_VALIDATE_INT ) ) || $input < 0 ) ? false : true;
    }

    /**
     * @param string $date
     * @return int
     * @throws HandlerLogicIncorrectDateFormatException
     * @throws HandlerLogicIncorrectTimestampException
     */
    private function dateToTimestamp( $date )
    {
        $date_time = explode( 'T', $date );
        if ( strlen( $date_time[0] ) !== 10 || strpos( $date_time[0], '-' ) !==4 ) {
            throw new HandlerLogicIncorrectDateFormatException();
        }

        $timestamp = strtotime( $date_time[0] );
        if ( $timestamp === false ) {
            throw new HandlerLogicIncorrectTimestampException();
        }
print '<pre>';
var_dump($timestamp);
print '</pre>';
        return $timestamp;
    }

    /**
     * Recurrent method. Returns data from all pages.
     *
     * @param int $page
     * @param array $data
     * @return array
     */
    private function getAdPage( $page = 0, array $data = array() )
    {
        try {
            $results = $this->soap->call( 'getAdvertisements', array(
                 array(
                    'firstResult' => $page,
                    'maxResults' => $this->getMaxResults(),
                    'langCode' => $this->getLumesseLanguage( $this->lang )
                )
            ) );

            $advertisements = array();
            if ( isset( $results->advertisementResult->advertisements->advertisement ) ) {

                if ( is_array( $results->advertisementResult->advertisements->advertisement ) ) {
                    $advertisements = $results->advertisementResult->advertisements->advertisement;
                }
                elseif( $results->advertisementResult->advertisements->advertisement instanceof \stdClass ) {
                    $advertisements[] = $results->advertisementResult->advertisements->advertisement;
                }
            }

            $data = array_merge( $data, $advertisements );

            if ( $this->nextPageExists( $page, $this->getMaxResults(), $this->getProcessLength( $results ) ) ) {
                $data = $this->getAdPage( $page + $this->getMaxResults(), $data );
            }

        }
        catch( \Exception $e ) {
            $data = array();
        }

        return $data;
    }

    /**
     * Returns all possible results
     */
    public function getAllAds()
    {
        if ( sizeof( $this->data ) === 0 ) {
            $this->data = $this->getAdPage();
        }

//        foreach( $this->data as $item ) {
//            print $item->id . "\n";
//        }

        return $this->data;
    }

    /**
     * Returns the value of maximum ads per page
     *
     * @return bool|int
     * @throws HandlerLogicIncorrectRangeException
     */
    private function getMaxResults()
    {
        if ( is_null( $this->max_results ) ) {
            $this->max_results = \eZINI::instance( 'ezlumesse.ini' )->variable( 'MainSettings', 'MaxResults' );
        }

        if ( !filter_var( $this->max_results, FILTER_VALIDATE_INT ) || $this->max_results < 0 || $this->max_results > 100 ) {
            throw new HandlerLogicIncorrectRangeException( '"MaxRange" setting needs be a correct integer from a range 0-100' );
        }

        return $this->max_results;
    }

    /**
     * Returns a language code. If no code provided as script option, default code is used.
     *
     * @return mixed|string
     */
    private function getLanguage()
    {
        if ( $this->options->hasAttribute( 'lang' ) ) {
            return $this->options->attribute( 'lang' );
        }

        return $this->lang;
    }

    /**
     * Gets the eZ language code as a parameter and returns adequate Lumesse lang code
     *
     * @param string $ez_lang_code
     * @throws HandlerLogicIncorrectLanguageCodeException
     * @return string
     */
    private function getLumesseLanguage( $ez_lang_code )
    {
        if ( ! \eZINI::instance( 'ezlumesse.ini' )->hasVariable( 'LanguageMapping', $ez_lang_code ) ) {
            throw new HandlerLogicIncorrectLanguageCodeException();
        }

        return  \eZINI::instance( 'ezlumesse.ini' )->variable( 'LanguageMapping', $ez_lang_code );
    }

    /**
     * Method used for data iteration.
     *
     * @return mixed
     */
    public function getNextRow()
    {
        $return = $this->data[key( $this->data )];
        next( $this->data );

        return $return;
    }

    /**
     * Reads the total results number from given object containing results and returns it.
     * In case when no objects is given, method returns default value
     *
     * @param \stdClass|null $results
     * @return int
     * @throws HandlerLogicTotalResultsMissingException
     */
    public function getProcessLength( $results = null )
    {
        if ( $this->process_length === 0 && !is_null( $results ) ) {

            if( !isset( $results->advertisementResult->totalResults ) ) {
                throw new HandlerLogicTotalResultsMissingException();
            }

            $this->process_length = $results->advertisementResult->totalResults;
        }

        return $this->process_length;
    }

    /**
     * Method checks whether next page of the results exists.
     * @param int $current_page
     * @param int $items_per_page
     * @param int $all_items
     * @return bool
     * @throws HandlerLogicIncorrectIntegersException
     */
    private function nextPageExists( $current_page, $items_per_page, $all_items )
    {
        if ( !$this->correctInteger( array( $current_page, $items_per_page, $all_items ) ) ) {
            throw new HandlerLogicIncorrectIntegersException();
        }

        $return = false;

        if ( ( $current_page + $items_per_page ) < $all_items ) {
            $return = true;
        }

        return $return;
    }

    public function processRow( $row )
    {
        $this->addNewObject( $row );
    }

    /**
     * Method converts given string into correct xmlblock value
     *
     * @param string $string
     * @param int $object_id
     * @return mixed
     * @throws HandlerLogicIncorrectXmlStringException
     * @throws HandlerLogicIncorrectObjectIdException
     */
    private function stringToXmlblock( $string, $object_id )
    {
        if( !is_string( $string ) ) {
            throw new HandlerLogicIncorrectXmlStringException();
        }
        
        if ( ( !is_int( $object_id ) && !filter_var( $object_id, FILTER_VALIDATE_INT ) ) || $object_id <= 0 ) {
            throw new HandlerLogicIncorrectObjectIdException();
        }
        
        $parser = new \eZSimplifiedXMLInputParser( $object_id, \eZXMLInputParser::ERROR_SYNTAX, \eZXMLInputParser::ERROR_ALL, true );
        $document = $parser->process( html_entity_decode( $string, ENT_QUOTES, "UTF-8" ) );

        return \eZXMLTextType::domString( $document );
    }
}