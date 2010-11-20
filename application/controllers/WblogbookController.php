<?php
/**
 * Copyright 2007, 2008, 2009, 2010 Yuri Timofeev tim4dev@gmail.com
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

class WblogbookController extends MyClass_ControllerAclAction
{

    // PRE code improperly processed in regexp in *_replace in index.phtml
    // код PRE неправильно обрабатывается regexp'ом в *_replace в index.phtml
    protected $aAllowedTags = array('pre','b', 'h1', 'h2', 'h3', 'p', 'i', 'em', 'u', 'br', 'code', 'del', 'sub',
        'sup', 'tt', 'a');
    protected $aAllowedAttrs = array('href');
    protected $id_insert = 0; // id вставленной записи


    function init()
    {
        parent::init();
		// load model
		Zend_Loader::loadClass('Wblogbook');
		// load validators
		Zend_Loader::loadClass('MyClass_Validate_Datetime');
		Zend_Loader::loadClass('MyClass_Validate_BaculaJobId');
		Zend_Loader::loadClass('MyClass_Validate_LogBookId');
		Zend_Loader::loadClass('Zend_Validate_Digits');
		Zend_Loader::loadClass('Zend_Validate_NotEmpty');
		Zend_Loader::loadClass('MyClass_SendEmail');
	}

    /**
     * Define order
     * Определяет порядок сортировки
     *
     * @param string $so1
     * @param string $so2
     */
    function defSortOrder($so1)
    {
        $sort_order = 'DESC';
        if ( isset($so1) ) {
            if ( trim($so1) == 'ASC' )
                $sort_order = 'ASC';
        }
        return $sort_order;
    }

	/**
	 * Строковое описание для порядка сортировки
	 *
	 * @param string $sorto
	 */
	function defStrSortOrder($sorto)
	{
	    if ( $sorto == 'ASC' ) {
            $str_sort_order = $this->view->translate->_('in ascending order');
        } else {
            $str_sort_order = $this->view->translate->_('in descending order');
        }
        return $str_sort_order;
	}

    /**
     * LogBook view action without any filters
     *
     */
    function indexAction()
    {
        $this->view->title = $this->view->translate->_("Logbook");
        //print_r($this->_request->getParams()); exit; //debug !!!
        $date_begin  = date('Y-m-d', time()-2678400);
        $date_end    = date('Y-m-d', time());
        // порядок сортировки
        $sort_order = 'DESC';
        $str_sort_order = $this->defStrSortOrder($sort_order);
        // даты
        $tmp_title = sprintf($this->view->translate->_(" %s (from %s to %s)"), $str_sort_order, $date_begin, $date_end);
        $this->view->title .= $tmp_title;
        $this->view->date_begin = $date_begin;
        $this->view->date_end = $date_end;
        // get data from model
        $logs = new wbLogBook();
        $ret = $logs->IndexLogBook($date_begin, $date_end, $sort_order);
        if ($ret)	{
            $this->view->result = $ret->fetchAll();
        }
    }


    /**
     * LogBook view action with filter by Date
     */
    function filterbydateAction()
    {
        $this->view->title = $this->view->translate->_("Logbook");
        //print_r($this->_request->getParams()); exit; //debug !!!
        $date_begin  = trim( $this->_request->getParam('date_begin', date('Y-m-d', time()-2678400)) );
        $date_end    = trim( $this->_request->getParam('date_end', date('Y-m-d', time())) );
        $unit_test = $this->_request->getParam('test', null);
        // порядок сортировки
        $sort_order     = $this->defSortOrder( trim( $this->_request->getParam('sortorder_by_date') ) );
        $str_sort_order = $this->defStrSortOrder($sort_order);
        // даты
        $tmp_title = sprintf($this->view->translate->_(" %s (from %s to %s)"), $str_sort_order, $date_begin, $date_end);
        $this->view->title .= $tmp_title;
        $this->view->date_begin = $date_begin;
        $this->view->date_end   = $date_end;
        // get data from model
        $logs = new wbLogBook();
        $ret = $logs->IndexLogBook($date_begin, $date_end, $sort_order);
        if ($ret)	{
           $this->view->result = $ret->fetchAll();
        }
        $printable = $this->_request->getParam('printable_by_date');
        if ( empty($printable) )
        {
            // not printable
            echo $this->renderScript('wblogbook/index.phtml');
        } else {
            // printable
            if ( !empty($unit_test) ) {
                // for unit tests
                $this->view->unit_test = 1;
            } else {
                // not tests
                $this->_helper->layout->setLayout('printable');
            }
            echo $this->renderScript('wblogbook/index-printable.phtml');
        }
    }


    /**
	 * LogBook view action with filter by logId
	 *
	 */
    function filterbyidAction()
    {
    	$id_begin = intval( $this->_request->getParam('id_begin', 0) );
    	$id_end   = intval( $this->_request->getParam('id_end', $id_begin) );
		if ( $id_end == 0 ) {
			 $id_end = $id_begin;
		}
    	//echo '<pre>id_begin = ' . $id_begin . '<br>id_end = ' . $id_end . '</pre>'; exit();

    	// порядок сортировки
      $sort_order     = $this->defSortOrder( trim($this->_request->getParam('sortorder_by_id')) );
      $str_sort_order = $this->defStrSortOrder($sort_order);

    	$this->view->title    = sprintf($this->view->translate->_("Logbook by Id %s (from %u to %u)"), $str_sort_order, $id_begin, $id_end);
    	$this->view->id_begin = $id_begin;
  		$this->view->id_end   = $id_end;

		// get data from model
    	$logs = new wbLogBook();
    	$ret = $logs->findLogBookById($id_begin, $id_end, $sort_order);
    	if ($ret)	{
    		$this->view->result = $ret->fetchAll();
    	}

    	$printable = $this->_request->getParam('printable_by_id');
    	if ( empty($printable) )
    	{
    		echo $this->renderScript('wblogbook/index.phtml');
    	} else {
    		$this->_helper->layout->setLayout('printable');
    		echo $this->renderScript('wblogbook/index-printable.phtml');
    	}
    }


    /**
     * LogBook view action full text search
     *
     */
    function searchtextAction()
    {
        $id_text = addslashes( substr( $this->_request->getParam('id_text'), 0, 250 ) );
        // порядок сортировки
        $sort_order     = $this->defSortOrder( trim($this->_request->getParam('sortorder_by_text')) );
        // unused ? $str_sort_order = $this->defStrSortOrder($sort_order);

        $this->view->title   = $this->view->translate->_("Logbook") . ". " . $this->view->translate->_("Search text");
        $this->view->id_text = $id_text;

        // get data from model
        if ( isset($id_text) )	{
            $logs = new wbLogBook();
            $ret = $logs->findLogBookByText($id_text, $sort_order);
            if ($ret)	{
                $this->view->result = $ret->fetchAll();
            }
        }

        $printable = $this->_request->getParam('printable_by_text');
        if ( empty($printable) )	{
            echo $this->renderScript('wblogbook/index.phtml');
        } else {
            $this->_helper->layout->setLayout('printable');
            echo $this->renderScript('wblogbook/index-printable.phtml');
        }
    }


    /**
	 * LogBook Add New Record
	 */
    function myAddRecord($logDateCreate, $logTxt)
    {
		// Returns (int) $value
		Zend_Loader::loadClass('Zend_Filter_Int');
		$intf = new Zend_Filter_Int();

		// Returns the string $value, removing all but alphabetic characters.
		// This filter includes an option to also allow white space characters.
		Zend_Loader::loadClass('Zend_Filter_Digits');
		$digit = new Zend_Filter_Digits();
		$digit->allowWhiteSpace = true;

        $logTypeId = $intf->filter(trim($this->_request->getPost('logTypeId')));

        $logbook = new wbLogBook();
        $data = array(
            'logDateCreate' => $logDateCreate,
            'logTxt'    => $logTxt,
            'logTypeId' => $logTypeId
        );

        $this->id_insert = $logbook->insert($data);
        if ( $this->id_insert ) {
            $email = new MyClass_SendEmail();
            // $from_email, $from_name, $to_email, $to_name, $subj, $body
            $email->mySendEmail(
                $this->view->config->webacula->email->from,
                $this->view->translate->_('Webacula Logbook'),
                $this->view->config->webacula->email->to_admin,
                $this->view->translate->_('Webacula admin'),
                $this->view->translate->_('Webacula Logbook Add record'),
                $this->view->translate->_('Create record :') . " " . $data['logDateCreate'] . "\n" .
                    $this->view->translate->_('Type record :')   . " " . $data['logTypeId'] . "\n" .
                    $this->view->translate->_("Text :")          ."\n-------\n" . $data['logTxt'] . "\n-------\n\n"
                );
        }
    }


    /*
     * Update Bacula tables:
     * set Reviewed = 1 in Job table
     * and insert new record into Log table
     */
    function myJobReviewed($jobid, $msg='')
    {
        if ($jobid <= 0)
            return;
        Zend_Loader::loadClass('Job');
        Zend_Loader::loadClass('Log');
        if (empty($msg))
            $msg = $this->view->translate->_("Bacula Job Reviewed. See Webacula LOGBOOK_ID=".$this->id_insert).'.';
        // read Comment from Job table
        $table = new Job();
        $where = $table->getAdapter()->quoteInto('JobId = ?', $jobid);
        $row   = $table->fetchRow($where);
        if ($row)
            $msg_job = $msg ."\n". $row->comment;
        else
            $msg_job = $msg;
        // change Job table
        $data = array(
            'Reviewed' => 1,
            'Comment'  => $msg_job
        );
        $where = $table->getAdapter()->quoteInto('JobId = ?', $jobid);
        $res = $table->update($data, $where);
        if ( $res ) {
            $email = new MyClass_SendEmail();
            // $from_email, $from_name, $to_email, $to_name, $subj, $body
            $email->mySendEmail(
                $this->view->config->webacula->email->from,
                $this->view->translate->_('Webacula Logbook'),
                $this->view->config->webacula->email->to_admin,
                $this->view->translate->_('Webacula admin'),
                $this->view->translate->_('Bacula Job Reviewed'),
                $this->view->translate->_('Job Id') ." ". $jobid ."\n". $msg_job
                );
        }
        unset($table);
        // add record in Log table
        $table = new Log();
        $data = array(
            'JobId' => $jobid,
            'Time' => date("Y-m-d H:i:s", time()),
            'LogText' => $msg
        );
        $table->insert($data);
    }



    /**
	 * LogBook Add From New Record action
	 *
	 */
    function addAction()
    {
    	$this->view->title = $this->view->translate->_("Logbook: add new record");
    	$this->view->wblogbook = new Wblogbook();
    	$this->view->amessages = array();

    	if ($this->_request->isPost() && $this->_request->getPost('hiddenNew'))	{
    		// validate

    		// ********************* validate datetime
			$validator_datetime = new MyClass_Validate_Datetime();

			$logDateCreate = trim($this->_request->getPost('logDateCreate', date("Y-m-d H:i:s", time() ) ) );

			if ( !$validator_datetime->isValid($logDateCreate) ) {
				$this->view->amessages = array_merge($this->view->amessages, $validator_datetime->getMessages());
			}

			// ********************* validate text
			// This filter returns the input string, with all HTML and PHP tags stripped from it,
    		// except those that have been explicitly allowed.
    		Zend_Loader::loadClass('Zend_Filter_StripTags');
    		// allow :
    		// construct($tagsAllowed = null, $attributesAllowed = null, $commentsAllowed = false)
			$strip_tags = new Zend_Filter_StripTags(
				$this->aAllowedTags, $this->aAllowedAttrs, false);

			$logTxt    = trim($strip_tags->filter($this->_request->getPost('logTxt')));
			$validator_nonempty = new Zend_Validate_NotEmpty();

			if ( !$validator_nonempty->isValid($logTxt) ) {
				$this->view->amessages = array_merge($this->view->amessages, $validator_nonempty->getMessages());
			}

        	// *** validate pseudo tag BACULA_JOBID
            $validator_baculajobid = new MyClass_Validate_BaculaJobId();
            if ( !$validator_baculajobid->isValid( $logTxt ) ) {
                $this->view->amessages = array_merge($this->view->amessages, $validator_baculajobid->getMessages());
            }

    	    // *** validate pseudo tag LOGBOOK_ID
            $validator_logbookid = new MyClass_Validate_LogbookId();
            if ( !$validator_logbookid->isValid( $logTxt ) ) {
                $this->view->amessages = array_merge($this->view->amessages, $validator_logbookid->getMessages());
            }

            // ********************* final
            // add record into database
            if ( empty($this->view->amessages))     {
                // validation is OK. add record into logbook
                $this->myAddRecord($logDateCreate, $logTxt);
                // Reviewed feature
                $jobid     = intval($this->_request->getPost('jobid', 0));
                $joberrors = intval($this->_request->getParam('joberrors', 0));
                $reviewed  = $this->_request->getPost('reviewed', 0);
                if ( $reviewed && ($joberrors > 0) )
                    $this->myJobReviewed($jobid);
                elseif ($jobid)
                    $this->myJobReviewed($jobid, $this->view->translate->_("See also Webacula LOGBOOK_ID=".$this->id_insert).'.');
                //$msg = $this->view->translate->_("Bacula Job Reviewed. See Webacula LOGBOOK_ID=".$this->id_insert).'.';
                $this->_redirect('/wblogbook/index');
                return;
            }

            // ********************* save user input for correct this
            $this->view->wblogbook->logTxt    = $strip_tags->filter($this->_request->getPost('logTxt'));
            $this->view->wblogbook->logTypeId = $this->_request->getPost('logTypeId');
        } else {
            // ********************* setup empty record
            $this->view->wblogbook->logTxt    = null;
            $this->view->wblogbook->logTypeId = null;
        }

    	// draw form
    	Zend_Loader::loadClass('Wblogtype');

    	// get data from wbLogType
    	$typs = new Wblogtype();
    	$this->view->typs = $typs->fetchAll();
        // common fileds
        $this->view->wblogbook->logDateLast = null;
        $this->view->wblogbook->logId = null;
        $this->view->wblogbook->logDateCreate = date('Y-m-d H:i:s', time());
        $this->view->hiddenNew = 1;
        $this->view->aAllowedTags = $this->aAllowedTags;
    }



    /**
	 * LogBook modify Record action
	 *
	 */
    function modifyAction()
    {
    	$this->view->title = $this->view->translate->_("Logbook: modify record");
    	$this->view->wblogbook = new Wblogbook();
    	$this->view->amessages = array();

    	// ****************************** UPDATE record **********************************
    	if ( $this->_request->isPost() && $this->_request->getPost('hiddenModify') &&
    		$this->_request->getPost('act') == 'update')
    	{
    		$logid = trim($this->_request->getPost('logid'));

    		// ********************* validate datetime
    		$validator_datetime = new MyClass_Validate_Datetime();

			$logDateCreate = trim($this->_request->getPost('logDateCreate'));

			if ( !$validator_datetime->isValid($logDateCreate) ) {
				$this->view->amessages = array_merge($this->view->amessages, $validator_datetime->getMessages());
			}

			// ********************* validate text
			// This filter returns the input string, with all HTML and PHP tags stripped from it,
    		// except those that have been explicitly allowed.
    		Zend_Loader::loadClass('Zend_Filter_StripTags');
    		// allow :
    		// construct($tagsAllowed = null, $attributesAllowed = null, $commentsAllowed = false)
			$strip_tags = new Zend_Filter_StripTags(
				$this->aAllowedTags, $this->aAllowedAttrs, false);
			$logTxt    = trim($strip_tags->filter($this->_request->getPost('logTxt')));
			$validator_nonempty = new Zend_Validate_NotEmpty();

			if ( !$validator_nonempty->isValid($logTxt) ) {
				$this->view->amessages = array_merge($this->view->amessages, $validator_nonempty->getMessages());
			}

    	    // *** validate pseudo tag BACULA_JOBID
            $validator_baculajobid = new MyClass_Validate_BaculaJobId();
            if ( !$validator_baculajobid->isValid( $logTxt ) ) {
                $this->view->amessages = array_merge($this->view->amessages, $validator_baculajobid->getMessages());
            }

        	// *** validate pseudo tag LOGBOOK_ID
            $validator_logbookid = new MyClass_Validate_LogbookId();
            if ( !$validator_logbookid->isValid( $logTxt ) ) {
                $this->view->amessages = array_merge($this->view->amessages, $validator_logbookid->getMessages());
            }

			// ********************* final
			// update record into database
			if ( empty($this->view->amessages) )	{
				// validation is OK. add record into logbook

    			$table = new wbLogBook();
				$data = array(
				    'logDateCreate' => $logDateCreate,
				    'logDateLast'   => date('Y-m-d H:i:s', time()),
				    'logTypeId' 	=> (int) trim($this->_request->getPost('logTypeId')),
					'logTxt'   => $logTxt,
    				'logIsDel' => (int) trim($this->_request->getPost('isdel'))
				);

				$where = $table->getAdapter()->quoteInto('logId = ?', $logid);
				$res = $table->update($data, $where);

				// send email
				if ( $res ) {
				    $email = new MyClass_SendEmail();
                    // $from_email, $from_name, $to_email, $to_name, $subj, $body
                    $email->mySendEmail(
                        $this->view->config->webacula->email->from,
                        $this->view->translate->_('Webacula Logbook'),
                        $this->view->config->webacula->email->to_admin,
                        $this->view->translate->_('Webacula admin'),
                        $this->view->translate->_('Webacula Logbook Update record'),
                        $this->view->translate->_('Create record :') . ' ' . $data['logDateCreate'] . "\n" .
                            $this->view->translate->_('Update record :') . ' ' . $data['logDateLast'] . "\n" .
                            $this->view->translate->_('Type record :')   . ' ' . $data['logTypeId'] . "\n" .
                            $this->view->translate->_("Text :") . "\n-------\n" . $data['logTxt'] . "\n-------\n\n"
                    );
				}
    			$this->_redirect('/wblogbook/index');
    			return;
			}
    		// ********************* save user input for correct this
    		$this->view->wblogbook->logTxt    = $strip_tags->filter($this->_request->getPost('logTxt'));
        	$this->view->wblogbook->logTypeId = $this->_request->getPost('logTypeId');
    	}
    	// ****************************** DELETE record **********************************
    	if ( $this->_request->isPost() && $this->_request->getPost('hiddenModify') &&
    		$this->_request->getPost('act') == 'delete')
    	{
    		$logid = trim($this->_request->getPost('logid'));
    		$table = new wbLogBook();
			$data = array(
    			'logIsDel' => '1'
			);

			$where = $table->getAdapter()->quoteInto('logId = ?', $logid);
			$res = $table->update($data, $where);

            // send email
            if ( $res ) {
                $email = new MyClass_SendEmail();
                // $from_email, $from_name, $to_email, $to_name, $subj, $body
                $email->mySendEmail(
                    $this->view->config->webacula->email->from,
                    $this->view->translate->_('Webacula Logbook'),
                    $this->view->config->webacula->email->to_admin,
                    $this->view->translate->_('Webacula admin'),
                    $this->view->translate->_('Webacula Logbook Delete record'),
                    $this->view->translate->_("LogId record :") ." " . $logid . "\n"
                );
			}

    		$this->_redirect('/wblogbook/index');
    		return;
    	}
    	// ****************************** UNDELETE record **********************************
    	if ( $this->_request->isPost() && $this->_request->getPost('hiddenModify') &&
    		$this->_request->getPost('act') == 'undelete')
    	{
    		$logid = trim($this->_request->getPost('logid'));
    		$table = new wbLogBook();
			$data = array(
    			'logIsDel' => '0'
			);
			$where = $table->getAdapter()->quoteInto('logId = ?', $logid);
			$res = $table->update($data, $where);
			// send email
            if ( $res ) {
                $email = new MyClass_SendEmail();
                // $from_email, $from_name, $to_email, $to_name, $subj, $body
                $email->mySendEmail(
                    $this->view->config->webacula->email->from,
                    $this->view->translate->_('Webacula Logbook'),
                    $this->view->config->webacula->email->to_admin,
                    $this->view->translate->_('Webacula admin'),
                    $this->view->translate->_('Webacula Logbook UnDelete record'),
                    $this->view->translate->_("LogId record :") . " " . $logid . "\n"
                );
			}

    		$this->_redirect('/wblogbook/index');
    		return;
    	}
    	// ********************* READ ORIGINAL RECORD from database ****************
    	if ( $this->_request->isPost() )
    	{
	    	$logid = trim($this->_request->getPost('logid'));
			// get data from table
			$logs = new wbLogBook();
			$where = $logs->getAdapter()->quoteInto('logId = ?', $logid);
			$row = $logs->fetchRow($where);

   			if ($row)	{
    			$this->view->wblogbook->logId	  = $row->logid;
    			$this->view->wblogbook->logDateCreate = $row->logdatecreate;
				$this->view->wblogbook->logTxt    = $row->logtxt;
        		$this->view->wblogbook->logTypeId = $row->logtypeid;
        		$this->view->wblogbook->logIsDel  = $row->logisdel;
    		}
        }
        // for draw form
        Zend_Loader::loadClass('Wblogtype');
        // get data from wbLogType
        $typs = new Wblogtype();
        $this->view->typs = $typs->fetchAll();
        // common fileds
        $this->view->wblogbook->logDateLast = date('Y-m-d H:i:s', time());
        $this->view->hiddenModify = 1;
        $this->view->aAllowedTags = $this->aAllowedTags;
    }


    /**
     * Write record about Job to LogBook
     */
    function writelogbookAction()
    {
        $jobid    = intval($this->_request->getParam('jobid'));
        $name_job = trim($this->_request->getParam('name_job'));
        $endtime  = trim($this->_request->getParam('endtime'));
        $joberrors= intval($this->_request->getParam('joberrors', 0));

        $this->view->title = $this->view->translate->_("Logbook: add new record");
        $this->view->wblogbook = new Wblogbook();
        $this->view->amessages = array();
        // setup new record
        // get data from table
        Zend_Loader::loadClass('Wbjobdesc');
        $table = new wbJobDesc();
        $select  = $table->select()->where('name_job = ?', $name_job);
        $row = $table->fetchRow($select);
        if ($row) {
            $this->view->wblogbook->logTxt = $row->description."\n".
                $row->retention_period."\n\n".
                "$name_job $endtime\n\n".
                "BACULA_JOBID=$jobid\n";
        } else {
            $this->view->wblogbook->logTxt = "$name_job\n$endtime\n".
                "BACULA_JOBID=$jobid\n";
        }
        if ( $joberrors > 0 )   {
            $this->view->wblogbook->logTypeId = 255; // Error
            $this->view->wblogbook->logTxt .=  "\nJob Errors : ".$joberrors."\n";
        }   else
            $this->view->wblogbook->logTypeId = 20; // OK
        // get data from wbLogType
        Zend_Loader::loadClass('Wblogtype');
        $typs = new Wblogtype();
        $this->view->typs = $typs->fetchAll();
        // common fileds
        $this->view->wblogbook->logDateLast = null;
        $this->view->wblogbook->logId = null;
        $this->view->wblogbook->logDateCreate = date('Y-m-d H:i:s', time());
        $this->view->hiddenNew = 1;
        $this->view->aAllowedTags = $this->aAllowedTags;
        $this->view->joberrors = $joberrors;
        $this->view->jobid = $jobid;
        echo $this->renderScript('/wblogbook/add.phtml');
    }


}
