<?php
/**
 *
 * Copyright 2007, 2008, 2010 Yuri Timofeev tim4dev@gmail.com
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
 *
 * @author Yuri Timofeev <tim4dev@gmail.com>
 * @package webacula
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 *
 */

require_once 'Zend/Controller/Action.php';

class ChartController extends MyClass_ControllerAclAction
{

    function init()
    {
        parent::init();
        // Disable view script autorendering
        $this->_helper->viewRenderer->setNoRender();

        Zend_Loader::loadClass('Timeline');
        // for input field validation
        Zend_Loader::loadClass('Zend_Validate');
        Zend_Loader::loadClass('Zend_Filter_Input');
        Zend_Loader::loadClass('Zend_Validate_StringLength');
        Zend_Loader::loadClass('Zend_Validate_NotEmpty');
        Zend_Loader::loadClass('Zend_Validate_Date');
        $validators = array(
            '*' => array(
                new Zend_Validate_StringLength(1, 255)
            ),
            'datetimeline' => array(
                'NotEmpty',
                'Date'
            )
        );
        $filters = array(
            '*'  => 'StringTrim'
        );
        $this->input = new Zend_Filter_Input($filters, $validators);
    }


    /**
     * Create Image for Timeline of Jobs
     *
     * @return image
     */
    function timelineAction()
    {
        // workaround for unit tests 'Action Helper by name Layout not found'
        if ($this->_helper->hasHelper('layout')) {
            $this->_helper->layout->disableLayout(); // disable layouts
        }
        // http://localhost/webacula/chart/timeline/datetimeline/2010-01-08
        // check GD lib (php-gd)
        if ( !extension_loaded('gd') ) {
            // No GD lib (php-gd) found
            $this->view->result = null;
            $this->getResponse()->setHeader('Content-Type', 'text/html; charset=utf-8');
            throw new Zend_Exception($this->view->translate->_('ERROR: The GD extension isn`t loaded. Please install php-gd package.'));
            return;
        }
        $this->input->setData( array('datetimeline' => $this->_request->getParam('datetimeline')) );
        if ( $this->input->isValid() ) {
            $date = $this->input->getEscaped('datetimeline');
        } else {
            $this->view->result = null;
            return;
        }

        if ( empty($date)  )
            return; // Nothing data to graph

        $timeline = new Timeline;
        $img = $timeline->createTimelineImage($date, true, $this->view->translate, 'normal');
        // Set the headers
        $this->getResponse()->setHeader('Content-Type', 'image/png');
        // Output a PNG image to either the browser or a file :
        // bool imagepng ( resource image [, string filename [, int quality [, int filters]]] )
        $res = imagepng($img, null, 5);
    }



    /**
     * Create Image Timeline for Dashboard
     *
     * @return image
     */
    function timelineDashboardAction()
    {
        // workaround for unit tests 'Action Helper by name Layout not found'
        if ($this->_helper->hasHelper('layout')) {
            $this->_helper->layout->disableLayout(); // disable layouts
        }
        // http://localhost/webacula/chart/timeline/datetimeline/2010-01-08
        // check GD lib (php-gd)
        if ( !extension_loaded('gd') ) {
            // No GD lib (php-gd) found
            $this->view->result = null;
            return;
        }
        $timeline = new Timeline;
        $img = $timeline->createTimelineImage(date('Y-m-d', time()), true, $this->view->translate, 'small');
        // Set the headers
        $this->getResponse()->setHeader('Content-Type', 'image/png');
        // Output a PNG image to either the browser or a file :
        // bool imagepng ( resource image [, string filename [, int quality [, int filters]]] )
        $res = imagepng($img, null, 5);
    }

}