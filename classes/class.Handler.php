<?php
namespace MakingWaves\eZLumesse;

class Handler extends \SQLIImportAbstractHandler implements \ISQLIImportHandler
{
    // Identifier of sqliimport handler
    const HANDLER_IDENTIFIER = 'ezlumesse';

    // Name of sqliimport handler
    const HANDLER_NAME = 'eZLumesse integration';

    /**
     * Constructor
     * @param \SQLIImportHandlerOptions $options
     */
    public function __construct( \SQLIImportHandlerOptions $options = null )
    {
        parent::__construct( $options );
    }

    /**
     * Main method called to configure/initialize handler.
     * Here you may read your data to import
     */
    public function initialize()
    {
        $soap = new Soap();

        $data = $soap->ws->__call( 'getAdvertisements', array(  ) );
        print '<pre>';
        print_r($data);
        print '</pre>';
    }

    /**
     * Get the number of iterations needed to complete the process.
     * For example, if you have 150 XML nodes to process, you may return 150.
     * This is needed to display import progression in admin interface
     * @return int
     */
    public function getProcessLength()
    {
        return 1;
    }

    /**
     * Must return next row to process.
     * In an iteration over several XML nodes, you'll return the current node (like current() function for arrays)
     * @return SimpleXMLElement|SimpleXMLIterator|DOMNode|SQLICSVRow
     */
    public function getNextRow()
    {

    }

    /**
     * Main method to process current row returned by getNextRow() method.
     * You may throw an exception if something goes wrong. It will be logged but won't break the import process
     * @param mixed $row Depending on your data format, can be DOMNode, SimpleXMLIterator, SimpleXMLElement, CSV row...
     */
    public function process( $row )
    {

    }

    /**
     * Final method called at the end of the handler process.
     */
    public function cleanup()
    {

    }

    /**
     * Returns full handler name
     * @return string
     */
    public function getHandlerName()
    {
        return self::HANDLER_NAME;
    }

    /**
     * Returns handler identifier, as in sqliimport.ini
     * @return string
     */
    public function getHandlerIdentifier()
    {
        return self::HANDLER_IDENTIFIER;
    }

    /**
     * Returns notes for import progression. Can be any string (an ID, a reference...)
     * Can be for example ID of row your import handler has just processed
     * @return string
     */
    public function getProgressionNotes()
    {

    }
} 