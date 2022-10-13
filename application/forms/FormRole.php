<?php
/**
 * Copyright 2010 Yuriy Timofeev tim4dev@gmail.com
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
 * @author Yuriy Timofeev <tim4dev@gmail.com>
 * @package webacula
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 *
 */
require_once 'Zend/Form.php';
require_once 'Zend/Form/Element/Submit.php';



class FormRole extends Zend_Form
{

    protected $translate;
    protected $elDecorators = array('ViewHelper', 'Errors'); // , 'Label'
    protected $_roleid;
	protected $_action_cancel = '';


    
    public function __construct($options = null, $roleid = null) {
        // The init() method is called inside parent::__construct()
        $this->_roleid = $roleid;
        parent::__construct($options);
    }


    public function init()
    {
        Zend_Loader::loadClass('Zend_Validate_Regex');
        $this->translate = Zend_Registry::get('translate');
        //Zend_Form::setDefaultTranslator( Zend_Registry::get('translate') );
        // set method to POST
        $this->setMethod('post');
        /*
         * hidden fields
         */
        $acl = $this->addElement('hidden', 'acl', array(
            'decorators' => $this->elDecorators
        ));
        $role_id = $this->addElement('hidden', 'role_id', array(
            'decorators' => $this->elDecorators
        ));
        /*
         * Order role
         */
        $order = $this->createElement('text', 'order', array(
            //'decorators' => $this->elDecorators,
            'label'     => $this->translate->_('Order').'*',
            'required'  => true,
            'class'     => 'form-control',
            'size'      => 3,
            'maxlength' => 5
        ));
        $order->addValidator('Int')
              ->setRequired(true);
        /*
         * Name role
         */
        $name = $this->createElement('text', 'role_name', array(
            //'decorators' => $this->elDecorators,
            'label'     => $this->translate->_('Name').'*',
            'required'  => true,
            'class'     => 'form-control',
            'size'      => 30,
            'maxlength' => 50
        ));
        $name_validator = new Zend_Validate_Regex('/^[a-zA-Z0-9_]+$/');
        $name_validator->setMessage( $this->translate->_('Role name incorrect. This contains only english alphabetical characters, digits and underscore.'));
        $name->addValidator('StringLength', false, array(2, 50))
             ->addValidator($name_validator)
             ->setRequired(true);
        /*
         * Description role
         */
        $description = $this->createElement('textarea', 'description', array(
            //'decorators' => $this->elDecorators,
            'label'     => $this->translate->_('Description').'*',
            'required'  => true,
            'class'     => 'form-control',
            'cols' => 50,
            'rows' => 3
        ));
        $description->setRequired(true);
        /*
         * Inherited role id
         */       
        Zend_Loader::loadClass('Wbroles');
        $table = new Wbroles();
        if ($this->_roleid)
            $where = $table->getAdapter()->quoteInto('id != ?', $this->_roleid);
        else
            $where = null;
        $rows  = $table->fetchAll($where, 'id');
        // create element
        $inherit_id = $this->createElement('select', 'inherit_id', array(
            'label'    => $this->translate->_('Inherited role'),
            'class' => 'form-control',
            'size' => 10
        ));
        $inherit_id->addMultiOption('', '');
        foreach( $rows as $v) {
            $inherit_id->addMultiOption( $v['id'], $v['name'] );
        }
        unset ($table);
        /*
         * submit button
         */
        $submit_button = new Zend_Form_Element_Submit('submit_button',array(
            'decorators' => $this->elDecorators,
            'id'    => 'ok_'.__CLASS__,
            'class' => 'btn btn-default',
            'label' => $this->translate->_('Submit Form')
        ));
        /*
         * cancel button
         */
        $cancel_button = new Zend_Form_Element_Submit('cancel_button',array(
            'decorators' => $this->elDecorators,
            'id'    => 'cancel_'.__CLASS__,
            'class' => 'btn btn-default',
            'label' => $this->translate->_('Cancel')
        ));
        /*
         *  add elements to form
         */
        $this->addElements( array(
            $order,
            $name,
            $description,
            $inherit_id,
            $submit_button,
            $cancel_button
        ));
    }

    public function setActionCancel($url = '')
    {
        $this->_action_cancel = $url;
    }



    public function getActionCancel()
    {
        return $this->_action_cancel;
    }	


}
