<?php

namespace Icinga\Module\Aquilon\ProvidedHook\Director;

use Icinga\Module\Director\Hook\ImportSourceHook;
use Icinga\Module\Director\Web\Form\QuickForm;
use Icinga\Module\Aquilon\ProvidedHook\Director\Aq;
use Exception;

/**
 * Class ImportSource
 * @package Icinga\Module\Aquilon\ProvidedHook\Director
 */
class ImportSource extends ImportSourceHook
{
    public function fetchData()
    {
        $aq = new Aq($this->getSetting('baseurl'), $this->getSetting('profiledir'));
        return $aq->getHosts();
    }

    public static function addSettingsFormFields(QuickForm $form)
    {
        $aq = new Aq("http://aquilon.gridpp.rl.ac.uk", "/tmp");
        $form->addElement('text', 'baseurl', array(
            'label' => $form->translate('Base URL'),
            'required' => true,
            'description' => $form->translate(
                'API url for your instance, e.g. http://aquilon.gridpp.rl.ac.uk'
            )
        ));

        $form->addElement('text', 'profiledir', array(
            'label' => $form->translate('Profile Directory'),
            'required' => true,
            'description' => $form->translate(
                'Location on filesystem where to store profiles, eg. /var/www/html/cache/'
            )
        ));

        $form->addElement('ExtensibleSet', 'personalities', array(
            'label'    => $form->translate('Personalities'),
            'required' => true,
            'value'    => $aq->getPersonalities()
        ));
    }

    public function listColumns()
    {
        $columns = array("hostname", "shortname", "address", "personality");

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