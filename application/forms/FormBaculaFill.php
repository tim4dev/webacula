<?php
/**
 * Copyright 2010 Yuri Timofeev tim4dev@gmail.com
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
require_once 'Zend/Form/Element/Reset.php';


class FormBaculaFill extends Zend_Form
{

    protected $translate;
    protected $elDecorators = array('ViewHelper', 'Errors'); // , 'Label'
    protected $table_bacula;
    protected $table_webacula;
    protected $role_id;
    protected $role_name;

    

    /**
     * @param <type> $options
     * @param <type> $userid
     * @param <type> $action update | add
     */
    public function __construct($table_bacula, $table_webacula, $role_id, $role_name, $options = null) {
        // The init() method is called inside parent::__construct()
        $this->table_bacula   = $table_bacula;
        $this->table_webacula = $table_webacula;
        $this->role_id        = $role_id;
        $this->role_name      = $role_name;
        parent::__construct($options);
    }
    

    public function init()
    {       
        $this->translate = Zend_Registry::get('translate');
        //Zend_Form::setDefaultTranslator( Zend_Registry::get('translate') );
        // set method to POST
        $this->setMethod('post');
        /*
         * hidden fields
         */
        $role_id = $this->addElement('hidden', 'role_id', array(
            'decorators' => $this->elDecorators,
            'value' => $this->role_id
        ));
        $role_name = $this->addElement('hidden', 'role_name', array(
            'decorators' => $this->elDecorators,
            'value' => $this->role_name
        ));
        /*
         * From Bacula database
         */
        Zend_Loader::loadClass('Wbroles');
        $table = new Wbroles();
        $data = $table->getBaculaFill($this->table_bacula, $this->table_webacula, $this->role_id);
        $bacula_fill = $this->createElement('multiselect', 'bacula_fill', array(
            'label'    => $this->translate->_('From Bacula database'),
            'class' => 'ui-select',
            'size' => 10
        ));
        $bacula_fill->addMultiOptions(array( '*all*' => '*all*' ));
        foreach( $data as $v ) {
            $bacula_fill->addMultiOptions(array( $v['name'] => $v['name'] ));
        }
        unset ($table);
        /*
         * submit button
         */
        $submit = new Zend_Form_Element_Submit('submit',array(
            'decorators' => $this->elDecorators,
            'id'    => 'ok_'.__CLASS__,
            'class' => 'prefer_btn',
            'label' => $this->translate->_('Add')
        ));
        /*
         *  add elements to form
         */
        $this->addElements( array(
            $bacula_fill,
            $submit
        ));
    }



}