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
     * Default constructor
     */
    public function __construct()
    {
        $this->soap = new Soap();
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
                'searchCriteriaDto' => array(
                    'firstResult' => $page,
                    'maxResults' => $this->getMaxResults()
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
        $data = $this->getAdPage();

        foreach( $data as $item ) {
            print $item->id . "\n";
        }

        return $data;
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
}