<?php
/**
 * ezLumesse - import script
 * Script execute imports for all siteaccesses defined in ezlumesse.ini in section ImportSiteaccesses.
 */

include 'extension/ezlumesse/classes/class.Import.php';

try {
    $import = new \MakingWaves\eZLumesse\Import();
    $import->runImport();
} catch (Exception $e) {
    $cli->error('An error has occurred : ' . $e->getMessage());
}

?>