<?php

namespace Icinga\Module\Aquilon\ProvidedHook\Director;

use Icinga\Module\Director\Hook\ImportSourceHook;
use Icinga\Module\Director\Web\Form\QuickForm;
use Icinga\Module\Aquilon\ProvidedHook\Director\AquilonImport;
use Exception;
use Icinga\Application\Config;

/**
 * Class ImportSource
 * @package Icinga\Module\Aquilon\ProvidedHook\Director
 */
class ImportSource extends ImportSourceHook
{
    public function fetchData()
    {
        $config = Config::module('aquilon');
        $aq = new AquilonImport($this->getSetting('baseurl'), $this->getSetting('basedir'));
        return $aq->parseJSONData();
    }

    public static function addSettingsFormFields(QuickForm $form)
    {
        $config = Config::module('aquilon');
        $aq = new AquilonImport("http://aquilon.gridpp.rl.ac.uk/cgi-bin/report/host_grn_personality_archetype", "/usr/share/icingaweb2/modules/aquilon/library/Aquilon/ProvidedHook/Director/ArchetypePersonalities.txt");

        $form->addElement('text', 'baseurl', array(
            'label' => $form->translate('Base URL'),
            'required' => true,
            'description' => $form->translate(
                'API url for your instance, e.g. http://aquilon.gridpp.rl.ac.uk'
            )
        ));

        $form->addElement('text', 'basedir', array(
            'label' => $form->translate('Base directory'),
            'required' => true,
            'description' => $form->translate(
                'Directory for your Archetype/Personality file e.g. /usr/share/icingaweb2/modules/aquilon/library/Aquilon/ProvidedHook/Director/ArchetypePersonalities.txt'
            )
        ));
    }

    public function listColumns()
    {
        $columns = array("hostname", "archetype", "personality", "email", "has_raid", "raid_model");

        return $columns;
    }

    public static function getDefaultKeyColumnName()
    {
        return 'hostname';
    }

    public function getName()
    {
        return 'Aquilon';
    }
}