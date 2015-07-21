<?php
namespace MakingWaves\eZLumesse;

class HandlerLogic
{
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
     * Contains the mapping of Lumesse Lovs attributes
     * @var array
     */
    private $lovs_data = array(
        'standard' => array(
            'first_level' => 'standardLovs',
            'second_level' => 'standardLov'
        ),
        'custom' => array(
            'first_level' => 'customLovs',
            'second_level' => 'customLov'
        ),
        'configurable' => array(
            'first_level' => 'configurableFields',
            'second_level' => 'configurableField'
        )
    );

    /**
     * Identifier on content class name
     */
    const CONTENT_CLASS_NAME = 'lumesse_offer';

    /**
     * Default constructor
     */
    public function __construct( \SQLIImportHandlerOptions $options )
    {
        $ezLumesseConfig = \eZINI::instance('ezlumesse.ini');
        $this->options = $options;
        $this->db = \eZDB::instance();
//        $this->lang = $this->getLanguage();
        $this->siteAccesses = $ezLumesseConfig->BlockValues['ImportSiteaccesses']['SiteAccess'];
    }


    /**
     * Adding new object into database
     *
     * @param \stdClass $row
     */
    private function addNewObject( \stdClass $row )
    {
        try {

            $this->db->begin();
            $this->lang = $this->getLumesseLanguage( $row->siteLanguage );
            $object = \SQLIContent::create( new \SQLIContentOptions( array(
                'class_identifier' => self::CONTENT_CLASS_NAME,
                'language' => $this->lang,
                'remote_id' => $this->getRemoteId( $row )
            ) ) );

            $this->setObjectData( $object, $row );

            $folder_publisher = \SQLIContentPublisher::getInstance();
            $folder_publisher->setOptions( new \SQLIContentPublishOptions( array(
                'parent_node_id' => $this->options->attribute( 'parent_node' )
            ) ) );

            $folder_publisher->publish( $object );

            $this->db->commit();
        }
        catch( \Exception $e ) {}
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
        if ( $timestamp === false || $timestamp < 0 ) {
            throw new HandlerLogicIncorrectTimestampException();
        }

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

            foreach ($this->siteAccesses as $site) {

                //get language from settings
                $siteSpec = explode(',', $site);
                $this->lang = $siteSpec[3];

                $this->soap = new Soap( $site );
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
                unset($soap);
            }
        }
        catch( \Exception $e ) {
            die('cant establish connection');
//            print_r($e);
//            $data = array();
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
        $key = key( $this->data );
        if( is_null( $key ) ) {
            return null;
        }

        $return = $this->data[$key];
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
     * Method generates the remote id for given row
     *
     * @param \stdClass $row
     * @return string
     */
    private function getRemoteId( \stdClass $row )
    {
        return md5( $row->applicationUrl . $row->siteLanguage );
    }

    /**
     * Returns a value of given standard lov identifier
     *
     * @param \stdClass $row
     * @param string $identifier
     * @param string $lov_type
     * @return string
     * @throws HandlerLogicIncorrectLovIdentifierException
     * @throws HandlerLogicIncorrectLovTypeException
     */
    private function getLov( \stdClass $row, $identifier, $lov_type = 'standard' )
    {
        if ( !array_key_exists( $lov_type, $this->lovs_data ) ) {
            throw new HandlerLogicIncorrectLovTypeException();
        }

        if ( !is_string( $identifier ) ) {
            throw new HandlerLogicIncorrectLovIdentifierException();
        }

        if ( isset( $row->{$this->lovs_data[$lov_type]['first_level']} ) ) {

            // second level contains multiple entries
            if ( is_array( $row->{$this->lovs_data[$lov_type]['first_level']}->{$this->lovs_data[$lov_type]['second_level']} ) ) {

                foreach( $row->{$this->lovs_data[$lov_type]['first_level']}->{$this->lovs_data[$lov_type]['second_level']} as $item ) {

                    if ( isset( $item->value ) && $item->value === $identifier ) {

                        if ( isset( $item->criteria->criterion->label ) ) {


                            if ($identifier == 'ContactPerson')
                            {
                                $return_data = array();
                                $return_data[] = join( ': ', array(
                                    $item->criteria->criterion->label,
                                    $item->criteria->criterion->value
                                ) );

                                $return_data[] = join( ': ', array('', '') );

                                return join( ', ', $return_data );
                            }

                            return $item->criteria->criterion->label;
                        }
                        elseif ( is_array( $item->criteria->criterion ) ) {

                            $return_data = array();
                            foreach( $item->criteria->criterion as $criterion ) {
                                $return_data[] = join( ': ', array(
                                    $criterion->label,
                                    $criterion->value
                                ) );
                            }

                            return join( ', ', $return_data );
                        }
                    }
                }
            }
            // second level is a single entry
            elseif ( $row->{$this->lovs_data[$lov_type]['first_level']}->{$this->lovs_data[$lov_type]['second_level']} instanceof \stdClass ) {

                if ( $row->{$this->lovs_data[$lov_type]['first_level']}->{$this->lovs_data[$lov_type]['second_level']}->value === $identifier ) {

                    if ( isset( $row->{$this->lovs_data[$lov_type]['first_level']}->{$this->lovs_data[$lov_type]['second_level']}->criteria->criterion->label ) ) {
                        return $row->{$this->lovs_data[$lov_type]['first_level']}->{$this->lovs_data[$lov_type]['second_level']}->criteria->criterion->label;
                    }
                }
            }
        }

        return '';
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

    /**
     * Processing given row
     *
     * @param \stdClass $row
     */
    public function processRow( \stdClass $row )
    {

        $object = \eZContentObject::fetchByRemoteID( $this->getRemoteId( $row ) );

        if ( is_null( $object ) ) {
            $this->addNewObject( $row );
        }
        else {
            $this->updateExistingObject( $row );
        }
    }

    /**
     * Setting the object data by given remote data
     *
     * @param \SQLIContent $object
     * @param \stdClass $row
     */
    private function setObjectData( \SQLIContent $object, \stdClass $row )
    {

        $object->fields[$this->lang]->name = $row->jobTitle;
        $object->fields[$this->lang]->url = $row->applicationUrl;

        $object->fields[$this->lang]->company_info = '';
        if ( ! is_null($row->customFields->customField[0]->value ) )
        {
            $object->fields[$this->lang]->company_info = $row->customFields->customField[0]->value;
        }

        $object->fields[$this->lang]->job_info = '';
        if ( ! is_null($row->customFields->customField[1]->value ) )
        {
            $object->fields[$this->lang]->job_info = $row->customFields->customField[1]->value;
        }

        $object->fields[$this->lang]->commence = $this->dateToTimestamp( $row->postingStartDate );
        $object->fields[$this->lang]->deadline = $this->dateToTimestamp( $row->postingEndDate );
        $object->fields[$this->lang]->schedule_type = $this->getLov( $row, 'ScheduleType' );
        $object->fields[$this->lang]->type_of_employment = $this->getLov( $row, 'ContractType' );
        $object->fields[$this->lang]->city = isset( $row->location ) ? $row->location : '';
        $object->fields[$this->lang]->region = $this->getLov( $row, 'Regioner', 'custom' );
        $object->fields[$this->lang]->country = $this->getLov( $row, 'Country1', 'custom' );
        $object->fields[$this->lang]->address = $this->getLov( $row, 'Administrativt', 'configurable' );
        $object->fields[$this->lang]->contact_person = $this->getLov( $row, 'ContactPerson', 'configurable' );
        $object->fields[$this->lang]->company_name = isset( $row->organizations->organization[0]->value ) ? $row->organizations->organization[0]->value : '';
        $object->fields[$this->lang]->show_apply_link = $this->getLov( $row, 'Show apply link', 'custom' ) === 'No' ? 0 : 1;

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

        $string = html_entity_decode( $string, ENT_QUOTES, "UTF-8" );

        //$string = strip_tags ($string, '<a><b><strong><p>');
        $string = preg_replace('/(<[^>]+) style=".*?"/i', '$1', $string);
        $string = preg_replace('/(<[^>]+) class=".*?"/i', '$1', $string);

//        $tidy = new \Tidy();
//        $config = array(
//            'input-encoding' => 'utf8',
//            'indent'     => true,
//            'clean'     => true,
//            'input-xml'  => true,
//            'output-xhtml' => true,
//            'wrap'       => false);
//        $tidy->parseString($string, $config);
//        $tidy->cleanRepair();
//        $string = $tidy->value;

        //jira: 43315-1786
        $string = str_replace( '<a href="mailto:agneta..stenberg@orklafoods.se">agneta.stenberg@orklafoods.se</a>', '<a href="mailto:agneta.stenberg@orklafoods.se">agneta.stenberg@orklafoods.se</a>', $string );

        $string = preg_replace( '/•[a-zA-Z]/', '• ', $string );
        $string = preg_replace( "/<[^\/>]*>([\s]?)*<\/[^>]*>/", '', $string );

        $parser = new \eZSimplifiedXMLInputParser($object_id);
        $xmlDocument = $parser->process($string);
        $xml = \eZXMLTextType::domString($xmlDocument);
        return $xml;;

    }

    /**
     * Method updates the content object of given row with row values
     * @param \stdClass $row
     */
    private function updateExistingObject( \stdClass $row )
    {
        $content = \SQLIContent::fromRemoteID( $this->getRemoteId( $row ) );
        $content->addLocation( \SQLILocation::fromNodeID( $this->options->attribute('parent_node') ) );

        $content->setOptions( new \SQLIContentOptions( array(
            'language' => $this->lang
        ) ) );

        $this->unhideNodes( $content->attribute( 'id' ) );
        $this->setObjectData( $content, $row );

        $publisher = \SQLIContentPublisher::getInstance();
        $publisher->publish( $content );
    }

    /**
     * Method works in case of Lumesse maintenance. After server is back, all fetched offers are unhided.
     * @param int $object_id
     */
    private function unhideNodes( $object_id )
    {
        $all_nodes = \eZContentObjectTreeNode::fetchByContentObjectID( $object_id );
        foreach( $all_nodes as $node ) {
            \eZContentObjectTreeNode::unhideSubTree( $node );
        }
    }

    /**
     * Method hides all the nodes which are not connected with currently fetched data
     */
    public function unpublishObsoleteAds()
    {
        $results = $this->fetchAllPublishedAds();

        foreach( $results as $result ) {

            $offer_wrapper = new OfferWrapper( $result );

            if ( $offer_wrapper->checkUrlExistance() === false ) {
                $offer_wrapper->hide();
            }
        }
    }

    /**
     * Returns all published lumesse offers
     * @return array
     */
    private function fetchAllPublishedAds()
    {
        $results = \eZFunctionHandler::execute( 'content', 'list', array(
            'parent_node_id' => $this->options->attribute( 'parent_node' ),
            'class_filter_type' => 'include',
            'class_filter_array' => array(
                self::CONTENT_CLASS_NAME
            ),
            'language' => $this->lang
        ) );

        if ( !is_array( $results ) ) {
            $results = array();
        }

        return $results;
    }
}