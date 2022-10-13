<?php
/**
 * Copyright 2009, 2010, 2011, 2014 Yuriy Timofeev <tim4dev@gmail.com>
 * @author Yuriy Timofeev <tim4dev@gmail.com>
 * @package webacula
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 */

class OtherControllerTest extends ControllerTestCase
{
    protected $locales = array('de' , 'en' , 'fr' , 'pt_BR' , 'ru', 'it', 'es');

    /**
	 * test language files
	 * @group lang
	 */
    public function testTranslate ()
    {
        print "\n" . __METHOD__ . ' ';
        $this->_rootLogin();
        $translate = Zend_Registry::get('translate');
        foreach ($this->locales as $locale) {
            echo ' ', $locale;
            $this->assertTrue($translate->isTranslated('Desktop', false, $locale), "invalid '$locale' language file!");
        }
        echo ' ';
    }
}
