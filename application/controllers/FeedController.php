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
require_once 'Zend/Controller/Action.php';

class FeedController extends Zend_Controller_Action
{

    function init ()
    {
        $this->_helper->viewRenderer->setNoRender(); // disable autorendering
        $this->baseUrl = $this->_request->getBaseUrl();
        $this->view->translate = Zend_Registry::get('translate');
        Zend_Loader::loadClass('Zend_Date');
        Zend_Loader::loadClass('Zend_Feed');
        // my classes
        Zend_Loader::loadClass('Job');
        Zend_Loader::loadClass('Media');
    }

    function feedAction ()
    {
        $test = $this->_request->getParam('test');
        if (empty($test))
            $this->_helper->layout->setLayout('printable'); // not test
        $config_feed = new Zend_Config_Ini('../application/config.ini', 'feed');
        // create array for feed
        $afeed = array('title' => $config_feed->feed_title , 'link' => $this->baseUrl . '/feed/feed' , 'description' => $config_feed->feed_desc , 'charset' => "UTF-8" , 'entries' => array());
        // terminated Jobs
        $jobs = new Job();
        $result = $jobs->GetLastJobs();
        foreach ($result as $item) {
            // convert date to timestamp format
            $date = new Zend_Date($item['starttime'], 'YYYY-MM-dd HH:mm:ss');
            $itemTimestamp = $date->getTimestamp();
            $content = '<pre><b>' . $this->view->translate->_("Job Id") . ' : </b>' . $item['jobid'] . '<br>' . '<b>' . $this->view->translate->_("Job Name") . ' : </b>' . $item['jobname'] . '<br>' . '<b>' . $this->view->translate->_("Status") . ' : </b>' . $item['jobstatuslong'] . '<br>' . '<b>' . $this->view->translate->_("Level") . ' : </b>' . $item['level'] . '<br>' . '<b>' . $this->view->translate->_("Client") . ' : </b>' . $item['clientname'] . '<br>' . '<b>' . $this->view->translate->_("Pool") . ' : </b>' . $item['poolname'] . '<br>' . '<b>' . $this->view->translate->_("Start Time") . ' : </b>' . $item['starttime'] . '<br>' . '<b>' . $this->view->translate->_("End Time") . ' : </b>' . $item['endtime'] . '<br>' . '<b>' . $this->view->translate->_("Duration") . ' : </b>' . $item['durationtime'] . '<br>' . '<b>' . $this->view->translate->_("Files") . ' : </b>' . number_format($item['jobfiles']) . '<br>' . '<b>' . $this->view->translate->_("Bytes") . ' : </b>' . $this->view->convBytes($item['jobbytes']) . '<br>' . '<b>' . $this->view->translate->_("Errors") . ' : </b>' . number_format($item['joberrors']) . '<br>' . '</pre>';
            $afeed['entries'][] = array('title' => $item['jobname'] . ' ' . $item['jobstatuslong'] , 'link' => $this->baseUrl . '/job/detail/jobid/' . $item['jobid'] , 'description' => $content , 'lastUpdate' => $itemTimestamp);
        }
        // Get info Volumes with Status of media: Disabled, Error
        $media = new Media();
        $ret = $media->GetProblemVolumes();
        $result = $ret->fetchAll();
        foreach ($result as $item) {
            $content = '<pre><b>' . $this->view->translate->_("Volume Name") . ' : </b>' . $item['volumename'] . '<br>' . '<b>' . $this->view->translate->_("Volume Status") . ' : </b>' . $item['volstatus'] . '<br>' . '</pre>';
            $afeed['entries'][] = array('title' => $this->view->translate->_("Volumes with errors") , 'link' => $this->baseUrl . '/volume/problem/' , 'description' => $content , 'lastUpdate' => time());
        }
        // import array to feed
        $feed = Zend_Feed::importArray($afeed, 'rss');
        // dump feed //  print "<pre>".$feed->saveXML();exit;
        if (empty($test)) {
            $feed->send();
        } else {
            print $feed->saveXML(); // for unit tests
        }
    }

}