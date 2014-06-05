<?php
namespace MakingWaves\eZLumesse;

/**
 * Class OfferWrapper
 * Contains a methods used for performing actions of job offer nodes
 *
 * There is no unit test for this class, since Customer didn't want it.
 *
 * @package MakingWaves\eZLumesse
 */
class OfferWrapper
{
    /**
     * @var \eZContentObjectTreeNode
     */
    private $offer_object;

    /**
     * Default construct
     * @param \eZContentObjectTreeNode $offer_object
     */
    public function __construct( \eZContentObjectTreeNode $offer_object )
    {
        $this->offer_object = $offer_object;
    }

    /**
     * Reads the url from the object and returns its validity
     * @return bool
     */
    public function checkUrlExistance()
    {
        $data_map = $this->offer_object->dataMap();
        $return = false;

        if ( isset( $data_map['url'] ) && $data_map['url'] instanceof \eZContentObjectAttribute ) {

            $return = $this->isOfferValid( $data_map['url']->content() );
        }

        return $return;
    }

    /**
     * Returns true when URL is correct and valid. Returns false when opposite.
     * @param $url
     * @return bool
     */
    private function isOfferValid( $url )
    {
        $is_valid = ( $this->isDomainCorrect( $url ) && !$this->incorrectRedirection( $url ) );

        return $is_valid;
    }

    /**
     * Method checks whether given URL matches one of domains defined in ini settings
     * @param $url
     * @return bool
     */
    private function isDomainCorrect( $url )
    {
        $allowed_domains = \eZINI::instance( 'ezlumesse.ini' )->variable( 'DisabledOffersSettings', 'UrlDomains' );
        $matches_domains = false;

        foreach( $allowed_domains as $domain ) {

            if( strpos( $url, $domain ) > -1 ) {

                $matches_domains = true;
                break;
            }
        }

        return $matches_domains;
    }

    /**
     * Fetches the url by curl and checks the correct redirection to ensure that offer is valid
     * @param $url
     * @return bool
     */
    private function incorrectRedirection( $url )
    {
        $contains_errors = false;
        $curl_handler = curl_init( urldecode( $url ) );
        $forbidden_sites = \eZINI::instance( 'ezlumesse.ini' )->variable( 'DisabledOffersSettings', 'ForbiddenRedirections' );

        curl_setopt( $curl_handler, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $curl_handler, CURLOPT_FOLLOWLOCATION, true );
        curl_exec( $curl_handler );
        $response_info = curl_getinfo( $curl_handler );
        curl_close( $curl_handler );

        if( is_array( $forbidden_sites ) ) {

            $contains_errors = in_array( $response_info['url'], $forbidden_sites );
        }

        return $contains_errors;
    }

    /**
     * Set the visibility of current node as hidden
     */
    public function hide()
    {
        \eZContentObjectTreeNode::hideSubTree( $this->offer_object );
    }
}