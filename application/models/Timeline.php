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
 *
 * Class for get data for graph timeline Job
 *
 * @package    webacula
 * @subpackage Helpers
 * @author Yuri Timofeev <tim4dev@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 */


class Timeline 
{

    public $db_adapter;

    public function __construct()
    {
        $this->db_adapter = Zend_Registry::get('DB_ADAPTER');
    }

	/**
	 * Put data from DB to 2D array
	 *
	 * @param integer $y - year - YYYY
	 * @param integer $m - month
	 * @param integer $d - day
	 * @return array 2D
	 */
	public function getDataTimeline($date)
    {
        if ( ! empty($date) )	{

			$db = Zend_Db_Table::getDefaultAdapter();

			// ********** query 1 *******************
			$select = new Zend_Db_Select($db);
    		$select->distinct();

            switch ($this->db_adapter) {
            case 'PDO_MYSQL':
                // http://dev.mysql.com/doc/refman/5.0/en/date-and-time-functions.html#function_date-format
                // %H - Hour (00..23)
                // %i - Minutes, numeric (00..59)
                $select->from('Job', array(
                'JobId', 'Name', 'StartTime', 'EndTime',
				'h1' => "DATE_FORMAT(StartTime, '%H')",
				'm1' => "DATE_FORMAT(StartTime, '%i')",
				"h2" => "DATE_FORMAT(EndTime, '%H')",
				'm2' => "DATE_FORMAT(EndTime, '%i')"));
                break;
            case 'PDO_PGSQL':
                // PostgreSQL
                // http://www.postgresql.org/docs/8.0/static/functions-formatting.html
                // HH24 - hour of day (00-23)
                // MI   - minute (00-59)
                $select->from('Job', array(
                'JobId', 'Name', 'StartTime', 'EndTime',
				'h1' => "to_char(StartTime, 'HH24')",
				'm1' => "to_char(StartTime, 'MI')",
				"h2" => "to_char(EndTime, 'HH24')",
				'm2' => "to_char(EndTime, 'MI')"));
                break;
			case 'PDO_SQLITE':
				// SQLite3 Documentation
				// http://sqlite.org/lang_datefunc.html
				// %H - Hour (00 .. 23)
				// %M - Minute (00 .. 59)
				// bug http://framework.zend.com/issues/browse/ZF-884
				$select->from('Job', array('jobid'=>'JobId', 'name'=>'Name', 'starttime'=>'StartTime', 'endtime'=>'EndTime',
					'h1' => "(strftime('%H',StartTime))",
					'm1' => "(strftime('%M',StartTime))",
					"h2" => "(strftime('%H',EndTime))",
					'm2' => "(strftime('%M',EndTime))"));
				break;
            }

    		$select->where("(StartTime >= '$date 00:00:00') AND (StartTime <= '$date 23:59:59') AND
				(EndTime <= '$date 23:59:59')");

			$select->order('JobId');

			//$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!

    		$stmt = $select->query();
			$result = $stmt->fetchAll();

			// забиваем результат в массив
			$i = 0;
			foreach($result as $line)	{
				$atime[$i]['jobid'] = $line['jobid'];
				$atime[$i]['name'] = $line['name'];
    			$atime[$i]['h1'] = $line['h1'] + ($line['m1'] / 60);
    			$atime[$i]['h2'] = $line['h2'] + ($line['m2'] / 60);
    			$atime[$i]['flag'] = 0; // признак, что задание уложилось в сутки
    			$atime[$i]['start'] = $line['starttime'];
    			$atime[$i]['end'] = $line['endtime'];
    			$i++;
			}

			$select->reset();
			unset($select);
			unset($stmt);


			// задания, старт или окончание которых лежат за пределами указанных суток

			// задание началось ранее

			// ********** query 2 *******************
			$select = new Zend_Db_Select($db);
    		$select->distinct();

    		switch ($this->db_adapter) {
            case 'PDO_MYSQL':
                // http://dev.mysql.com/doc/refman/5.0/en/date-and-time-functions.html#function_date-format
                // %H - Hour (00..23)
                // %i - Minutes, numeric (00..59)
                $select->from('Job', array(
                	'JobId', 'Name', 'StartTime', 'EndTime',
					'h1' => "DATE_FORMAT(StartTime, '%H')",
					'm1' => "DATE_FORMAT(StartTime, '%i')",
					'h2' => "DATE_FORMAT(EndTime, '%H')",
					'm2' => "DATE_FORMAT(EndTime, '%i')"));
                break;
            case 'PDO_PGSQL':
                // PostgreSQL
                // http://www.postgresql.org/docs/8.0/static/functions-formatting.html
                // HH24 - hour of day (00-23)
                // MI   - minute (00-59)
                $select->from('Job', array(
                	'JobId', 'Name', 'StartTime', 'EndTime',
					'h1' => "to_char(StartTime, 'HH24')",
					'm1' => "to_char(StartTime, 'MI')",
					'h2' => "to_char(EndTime, 'HH24')",
					'm2' => "to_char(EndTime, 'MI')"));
                break;
			case 'PDO_SQLITE':
				// SQLite3 Documentation
				// http://sqlite.org/lang_datefunc.html
                // %H - Hour (00 .. 23)
                // %M - Minute (00 .. 59)
                // bug http://framework.zend.com/issues/browse/ZF-884
                $select->from('Job', array('jobid'=>'JobId', 'name'=>'Name', 'starttime'=>'StartTime', 'endtime'=>'EndTime',
                    'h1' => "(strftime('%H',StartTime))",
                    'm1' => "(strftime('%M',StartTime))",
                    'h2' => "(strftime('%H',EndTime))",
                    'm2' => "(strftime('%M',EndTime))"));
				break;                
            }


    		$select->where("(EndTime > '$date 00:00:00') AND (EndTime <= '$date 23:59:59') AND
		    	(StartTime < '$date 00:00:00')");

			$select->order('JobId');

			//$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!

    		$stmt = $select->query();
			$result = $stmt->fetchAll();

			// забиваем результат в массив
			foreach($result as $line)	{
				$atime[$i]['jobid'] = $line['jobid'];
				$atime[$i]['name'] = $line['name'];
				$atime[$i]['h1'] = 0;
    			$atime[$i]['h2'] = $line['h2'] + ($line['m2'] / 60);
    			$atime[$i]['flag'] = -1; // признак, что задание началось ранее
    			$atime[$i]['start'] = $line['starttime'];
    			$atime[$i]['end'] = $line['endtime'];
    			$i++;
			}

			$select->reset();
			unset($select);
			unset($stmt);


			// задание закончилось позднее
			// ********** query 3 *******************
			$select = new Zend_Db_Select($db);
    		$select->distinct();

    		switch ($this->db_adapter) {
            case 'PDO_MYSQL':
                // http://dev.mysql.com/doc/refman/5.0/en/date-and-time-functions.html#function_date-format
                // %H - Hour (00..23)
                // %i - Minutes, numeric (00..59)
                $select->from('Job', array('JobId', 'Name', 'StartTime', 'EndTime',
				'h1' => "DATE_FORMAT(StartTime, '%H')",
				'm1' => "DATE_FORMAT(StartTime, '%i')",
				'h2' => "DATE_FORMAT(EndTime, '%H')",
				'm2' => "DATE_FORMAT(EndTime, '%i')"));
                break;
            case 'PDO_PGSQL':
                // PostgreSQL
                // http://www.postgresql.org/docs/8.0/static/functions-formatting.html
                // HH24 - hour of day (00-23)
                // MI   - minute (00-59)
                $select->from('Job', array('JobId', 'Name', 'StartTime', 'EndTime',
				'h1' => "to_char(StartTime, 'HH24')",
				'm1' => "to_char(StartTime, 'MI')",
				'h2' => "to_char(EndTime, 'HH24')",
				'm2' => "to_char(EndTime, 'MI')"));
                break;
			case 'PDO_SQLITE':
				// SQLite3 Documentation
                // http://sqlite.org/lang_datefunc.html
                // %H - Hour (00 .. 23)
                // %M - Minute (00 .. 59)
                // bug http://framework.zend.com/issues/browse/ZF-884
                 $select->from('Job', array('jobid'=>'JobId', 'name'=>'Name', 'starttime'=>'StartTime', 'endtime'=>'EndTime',
					'h1' => "(strftime('%H',StartTime))",
					'm1' => "(strftime('%M',StartTime))",
					'h2' => "(strftime('%H',EndTime))",
					'm2' => "(strftime('%M',EndTime))"));
            }

    		$select->where("(StartTime >= '$date 00:00:00') AND (StartTime <= '$date 23:59:59') AND
				(EndTime > '$date 23:59:59')");

			$select->order('JobId');

			//$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!

    		$stmt = $select->query();
			$result = $stmt->fetchAll();

			// забиваем результат в массив
			foreach($result as $line)	{
				$atime[$i]['jobid'] = $line['jobid'];
				$atime[$i]['name'] = $line['name'];
				$atime[$i]['h1'] = $line['h1'] + ($line['m1'] / 60);
    			$atime[$i]['h2'] = 23.9;
    			$atime[$i]['flag'] = 1; // признак, что задание окончилось позднее
    			$atime[$i]['start'] = $line['starttime'];
    			$atime[$i]['end'] = $line['endtime'];
    			$i++;
			}

			$select->reset();
			unset($select);
			unset($stmt);

			// задание началось ранее и закончилось позднее (очень длинное задание)
			// ********** query 4 *******************
			$select = new Zend_Db_Select($db);
    		$select->distinct();

    		switch ($this->db_adapter) {
            case 'PDO_MYSQL':
                // http://dev.mysql.com/doc/refman/5.0/en/date-and-time-functions.html#function_date-format
                // %H - Hour (00..23)
                // %i - Minutes, numeric (00..59)
                $select->from('Job', array(
                'JobId', 'Name', 'StartTime', 'EndTime',
				'h1' => "DATE_FORMAT(StartTime, '%H')",
				'm1' => "DATE_FORMAT(StartTime, '%i')",
				"h2" => "DATE_FORMAT(EndTime, '%H')",
				'm2' => "DATE_FORMAT(EndTime, '%i')"));
                break;
            case 'PDO_PGSQL':
                // PostgreSQL
                // http://www.postgresql.org/docs/8.0/static/functions-formatting.html
                // HH24 - hour of day (00-23)
                // MI   - minute (00-59)
                $select->from('Job', array(
                'JobId', 'Name', 'StartTime', 'EndTime',
				'h1' => "to_char(StartTime, 'HH24')",
				'm1' => "to_char(StartTime, 'MI')",
				"h2" => "to_char(EndTime, 'HH24')",
				'm2' => "to_char(EndTime, 'MI')"));
                break;
			case 'PDO_SQLITE':
				// SQLite3 Documentation
                // http://sqlite.org/lang_datefunc.html
                // %H - Hour (00 .. 23)
                // %M - Minute (00 .. 59)
                // bug http://framework.zend.com/issues/browse/ZF-884
                $select->from('Job', array('jobid'=>'JobId', 'name'=>'Name', 'starttime'=>'StartTime', 'endtime'=>'EndTime',
					'h1' => "(strftime('%H',StartTime))",
					'm1' => "(strftime('%M',StartTime))",
					'h2' => "(strftime('%H',EndTime))",
					'm2' => "(strftime('%M',EndTime))"));
                 break;
            }

    		$select->where("(StartTime < '$date 00:00:00') AND (EndTime > '$date 23:59:59')");
			$select->order('JobId');
			//$sql = $select->__toString(); echo "<pre>$sql</pre>"; exit; // for !!!debug!!!

    		$stmt = $select->query();
			$result = $stmt->fetchAll();

			// забиваем результат в массив
			foreach($result as $line)	{
				$atime[$i]['jobid'] = $line['jobid'];
				$atime[$i]['name'] = $line['name'];
				$atime[$i]['h1'] = 0;
    			$atime[$i]['h2'] = 23.9;
    			$atime[$i]['flag'] = 2; // признак, что задание началось ранее и окончилось позднее (очень длинное задание)
    			$atime[$i]['start'] = $line['starttime'];
    			$atime[$i]['end'] = $line['endtime'];
    			$i++;
			}

			$select->reset();
			unset($select);
			unset($stmt);
			//echo '<pre>'; print_r($atime); echo '</pre>'; exit(); // debud !!!

			// return
			if ( empty($atime) )	{
				return null;
			}	else {
				return $atime;
			}
    	}
    }


}
