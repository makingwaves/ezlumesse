<?php
namespace MakingWaves\eZLumesse;

/**
 * Class Import
 * Contains a methods used for import the lumesse job offers.
 *
 * @package MakingWaves\eZLumesse
 */
class Import
{
    public $cli;
    public $siteAccesses;

    public function __construct()
    {
        $this->cli = \eZCLI::instance();
        $this->cli->setUseStyles(true);
        $this->getSiteAccessesList();
    }

    /**
     * Load list of siteaccesses defined in ezlumesse.ini.
     */
    private function getSiteAccessesList()
    {
        $ezLumesseConfig = \eZINI::instance('ezlumesse.ini');
        $this->siteAccesses = $ezLumesseConfig->BlockValues['ImportSiteaccesses']['SiteAccess'];
    }

    /**
     * Get parent node defined in ezlumesse.ini.
     * @return int
     */
    private function getParentNode()
    {
        $ezLumesseConfig = \eZINI::instance('ezlumesse.ini');
        return $ezLumesseConfig->BlockValues['MainSettings']['JobOffersFolder'];
    }

    /**
     * Run import for all defined siteaccesses.
     */
    public function runImport()
    {
        if (!empty($this->siteAccesses[1])) {
            foreach ($this->siteAccesses as $site) {
                $siteData = explode(',', $site);
                if (isset($siteData[0]) && isset($siteData[1])) {
                    $this->executeSingleImport($siteData[0], $siteData[1]);
                }
            }
        } else {
            $this->cli->warning('Neither siteaccess is defined.');
        }
    }

    /**
     * @param $siteAccess
     * @param $lang
     * Execute import for defined single siteaccess.
     */
    public function executeSingleImport($siteAccess, $lang)
    {
        $this->cli->warning('Import for ' . $siteAccess . ' - ' . $lang . ' started.');
        exec('/usr/bin/php extension/sqliimport/bin/php/sqlidoimport.php -s' . $siteAccess . ' --source-handlers=ezlumesse --options="ezlumesse::parent_node=' . $this->getParentNode() . ',lang=' . $lang . '" >> /www/orkla/sites/www53/ezpublish_legacy/var/log/cronjob_logs/' .$siteAccess . '.log 2>&1');
    }
}

?>