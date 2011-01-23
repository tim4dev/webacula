<?php
/**
 * Copyright 2007, 2008, 2009 Yuri Timofeev tim4dev@gmail.com
 *
 * This file is part of Webacula.
 *
 * Webacula is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Webacula is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Webacula.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Yuri Timofeev <tim4dev@gmail.com>
 * @package webacula
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 *
 */
require_once 'Zend/Form.php';
require_once 'Zend/Form/Element/Submit.php';

class FormJobdesc extends Zend_Form
{
    protected $translate;


    public function init()
    {
        $this->translate = Zend_Registry::get('translate');
        //Zend_Form::setDefaultTranslator( Zend_Registry::get('translate') );
        // Set the method for the display form to POST
        $this->setMethod('post');

        $hidden_form1 = $this->createElement('hidden', 'form1', array('value' => '1', 'label' => '') );
        $hidden_form1->removeDecorator('Label');

        $hidden_desc_id = $this->createElement('hidden', 'desc_id');
        $hidden_desc_id->removeDecorator('Label');

        $name_job = $this->createElement('text', 'name_job', array(
            'label'      => $this->translate->_('Job Name'),
            'required'   => true,
            'size' => 30,
            'maxlength' => 64
        ));
        $name_job->addValidator('NotEmpty', false, null );
        $name_job->addValidator('StringLength', false, array(0, 64) );
        //$name_job->addDecorator('HtmlTag',  array('tag' => 'div', 'style'=>'margin-bottom:20px;'));

        $description = $this->createElement(
            'textarea', 'description', array(
            'label'      => $this->translate->_('Job Description'),
            'required'   => true,
            'cols' => 50,
            'rows' => 3
        ));
        $description->addValidator('NotEmpty', false, null );

        $retention_period = $this->createElement(
            'text', 'retention_period', array(
            'label'      => 'Retention period',
            'required'   => false,
            'size' => 16,
            'maxlength' => 32
        ));
        $retention_period->addValidator('StringLength', false, array(0, 32) );

        // submit button
        $submit = new Zend_Form_Element_Submit('submit',array(
            'class' => 'prefer_btn',
            'label' => $this->translate->_('Submit Form')
        ));

        // add elements to form
        $this->addElements( array(
            $hidden_desc_id,
            $hidden_form1,
            $name_job,
            $description,
            $retention_period,
            $submit
        ));
	}

}