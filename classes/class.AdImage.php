<?php
namespace MakingWaves\eZLumesse;

class AdImage
{
    /**
     * @var Soap
     */
    private $soap = null;

    /**
     * Default constructor
     */
    public function __construct()
    {
        if ( is_null( $this->soap ) ) {
            $this->soap = new Soap();
        }
    }

    public function getRemoteData( $id )
    {
        $results = $this->soap->call( 'getAdvertisementImages', array(
            array( 'postingTargetId' => $id )
        ) );
        return $results;
    }

}