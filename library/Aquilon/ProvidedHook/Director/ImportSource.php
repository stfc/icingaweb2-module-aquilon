<?php

namespace Icinga\Module\Aquilon\ProvidedHook\Director;

use Icinga\Module\Director\Hook\ImportSourceHook;
use Icinga\Module\Director\Web\Form\QuickForm;
use Exception;

/**
 * Class ImportSource
 * @package Icinga\Module\Puppetdb\ProvidedHook\Director
 */
class ImportSource extends ImportSourceHook
{
    public function fetchData()
    {
        $myarray = ["a", "b", "c"]

        return $myarray;
    }
}