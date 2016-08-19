<?php
/**
 * Copyright 2007, 2008, 2009, 2010, 2011, 2012 Yuriy Timofeev tim4dev@gmail.com
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
 * @author Wanderlei Hüttel <wanderlei.huttel@gmail.com>
 * @package webacula
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 *
 */

class Schedule
{
    const BEGIN_LIST = '/^======.*/'; // a sign of the beginning of the list (not always)
    const END_LIST   = '/^====$/';   // a sign of the end of the list (always present)

    const RUNNING_JOBS    = '/Running Jobs:/';        // top of the list of running jobs
    const NO_JOBS_RUNNING =  '/No Jobs running\./';   // no running jobs

    const SCHEDULED_JOBS    = '/Scheduled Jobs:/';     // top of the list of scheduled tasks
    const NO_SCHEDULED_JOBS = '/No Scheduled Jobs\./';  // No scheduled tasks

    const EMPTY_RESULT = 'EMPTY_RESULT';     // if nothing found

    public $db;
    public $db_adapter;
    protected $bacula_acl; // bacula acl


    public function __construct()
    {
        $this->config      = Zend_Registry::get('config');
        $this->sudo 	   = $this->config->general->bacula->sudo;
        $this->bconsole    = $this->config->general->bacula->bconsole;
        $this->bconsolecmd = $this->config->general->bacula->bconsolecmd;
        $cmd = '';
        if ( isset($this->sudo))	{
            // run with sudo
           $cmd = $this->sudo . ' ' . $this->bconsole . ' ' . $this->bconsolecmd;
        } else {
            $cmd = $this->bconsole . ' ' . $this->bconsolecmd;
        }
        $this->bconsolecmd = $cmd;
    }

    /**
    * Get the array with only numbers and return with the corresponding names.
    * Array month - 0=January, 1=February, ... 11=December
    * Array week of month - 0=1st, 1=2nd, ... 5=6st
    * Array day of week - 0=Sunday, 1=Monday, ... 6=Saturday
    * Array day - 0=1, 1=2, ... 30=31
    * Example: $month = array(0=>0, 1=>1, 2=>2, 3=>3, 4=>4, 5=>5, 6=>6, 7=>7, 8=>8, 9=>9, 10=>10, 11=>11)
    *  return: $month = January,February,... December);
    *      or: $month = January-December);
    *
    */
    public function sequenceShorten( $array_value, $start=0, $size=0, $array_label=null){


        $translate = Zend_Registry::get('translate');

        //create a new array
        $array_full = range(0,$size-1);

        //if array_label is null, return just numbers format
        if( $array_label == null){
            $array_label = $array_full;
        }//end if

        // use array_diff to get the missing elements
        $array_missing = array_diff($array_full, $array_value);

        //create a new array splitting when some key is missing with a separated key
        $count=0;
        for($i=0;$i<count($array_full);$i++){
            $value = $array_full[$i];
            $key = array_key_exists($value, $array_missing);
            if ($key == true){
                $count++;
            } else{
              $arr3[$count][] = $value;
            }
        } // end for

        //format output and return
        $text = "";
        foreach($arr3 as $row){
            $count = count($row);
            if( $count == 1 ){
               $text .= $translate->_($array_label[$row[0]]). ",";
            } else if( $count == 2 ){
               $text .= $translate->_($array_label[$row[0]]).",". $translate->_($array_label[$row[1]]).",";
            } else if( $count > 2 ){
                $text .= $translate->_($array_label[$row[0]])."-". $translate->_($array_label[$row[$count-1]]).",";
            }
        } // end foreach
        return substr($text,0,strlen($text)-1);

    }//end function



     /**
     * Get Schedules (from Director)
     *
     */
     public function getSchedule()
     {
    	$config = Zend_Registry::get('config');
        $array_month_short = array(0=>'Jan',1=>'Feb',2=>'Mar',3=>'Apr',4=>'May',5=>'Jun',6=>'Jul',7=>'Aug',8=>'Sep',9=>'Oct',10=>'Nov',11=>'Dec',);
        $array_month_long = array(0=>'January',1=>'February',2=>'March',3=>'April',4=>'May',5=>'June',6=>'July',7=>'August',8=>'Sepetember',9=>'October',10=>'November',11=>'December',);
        $array_weekday_short = array(0=>'Sun',1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat',);
        $array_weekday_long = array(0=>'Sunday',1=>'Monday',2=>'Tuesday',3=>'Wednesday',4=>'Thursday',5=>'Friday',6=>'Saturday');
        $array_weekmonth_short = array(0=>'1st',1=>'2nd',2=>'3rd',3=>'4th',4=>'5th',5=>'6th');
        $array_day_short = array(0=>1,1=>2,2=>3,3=>4,4=>5,5=>6,6=>7,7=>8,8=>9,9=>10,10=>11,11=>12,12=>13,13=>14,14=>15,15=>16,16=>17,17=>18,18=>19,19=>20,20=>21,21=>22,22=>23,23=>24,24=>25,25=>26,26=>27,27=>28,28=>29,29=>30,30=>31);

    	// check access to bconsole
    	if ( !file_exists($config->general->bacula->bconsole))	{
    		$aresult[] = 'NOFOUND';
    		return $aresult;
    	}

    	$bconsolecmd = '';
        if ( isset($config->general->bacula->sudo))	{
            // run with sudo
            $bconsolecmd = $config->general->bacula->sudo . ' ' . $config->general->bacula->bconsole . ' ' .
                    $config->general->bacula->bconsolecmd;
        } else {
            $bconsolecmd = $config->general->bacula->bconsole . ' ' . $config->general->bacula->bconsolecmd;
        }

        //===========================================================================
        // Get schedules names from bconsole
        //===========================================================================	
        $command_output = "";
        $return_var = 0;
        exec($bconsolecmd . 
        '<<EOF
        .schedule
        quit 
        EOF', $command_output, $return_var);
	
        foreach ($command_output as $line){
            $line = trim($line);
            $pattern = "((^Connecting|^1000 OK|^Enter a period|^\.|quit|^You have messages))";
            if ( !preg_match($pattern, $line)) {
                $array_schedule_name[] = $line;
            }
            if($line=="quit"){
                break;
            }		   
        } //end for each
        //echo "<pre>"; print_r($array_schedule_name); echo "</pre>";;// !!! DEBUG !!!


        //===========================================================================
        //#  Get information about of all schedules from bconsole and separate
        //#  information of schedules in an key in array
        //===========================================================================	   
        $array_2 = array();
	$command_output = "";
        $return_var = 0;
        exec($bconsolecmd . 
        '<<EOF
        show schedule
        quit 
        EOF', $command_output, $return_var);
	$key=-1;
        foreach ($command_output as $line){
	    $line = trim($line);
            if ( preg_match("((^Schedule))", $line)){
                foreach ($array_schedule_name as $value){
                    if ( preg_match("((".$value."))", $line)){
                       $status_schedule = str_replace("Schedule: Name=$value Enabled=","",$line);
                       $line = "Schedule: ". $value;
                       break;
                    }
                } // end foreach
                $key++;
                $array_schedule_options[$key]['name'] = $value;
                $array_schedule_options[$key]['status'] = $status_schedule;
                $array_schedule_options[$key]['schedule'] = null;
            } //end if
        
	    // Get just important information to identify options from schedules and ignore other  pattern negative
            $pattern = "((^Connecting|^1000 OK|^Enter a period|^\.|quit|^You have messages|^show schedule|^woy=|^use_cat=|^max_vols=|^VolUse=|^CleaningPrefix=|^RecyleOldest=|^MaxVolJobs=|^MigTime=|^JobRetention=))";
            if ( !preg_match($pattern, $line)) {
                $array_schedule[$key][] = str_replace("--> ","",$line);
            }
            if($line=="quit"){
                break;
            }		   
        } //end for each  
        //echo "<pre>"; print_r($array_schedule); echo "</pre>"; // !!! DEBUG !!! 
    
        //===========================================================================
        //#  Create secondary array with the options of everything schedule
        //===========================================================================	   
        // get the all lines from bconsole and separate groups lines per schedule names
        for($i=0;$i<count($array_schedule);$i++){
            $key = -1;
            for($j=0;$j<count($array_schedule[$i]);$j++){
	        $line = $array_schedule[$i][$j];
            
                //If find a schedule put all of the following lines in a one key array
                if ( preg_match("((^Schedule:))", $line)){
                    foreach ($array_schedule_name as $value){
                        if ( preg_match("((".$value."))", $line)){
                           $schedule = $value;
                           break;
                        } // end if
                    } // end foreach
                } //end if
            
                if ( preg_match("((^Run Level))", $line)){
                    $key++;
                    $v[$key]['level'] = str_replace("Run Level=","",$line);
                } //end if

                if ( preg_match("((^Pool))", $line)){
                    $v[$key]['pool'] = str_replace(" PoolType=Backup","", str_replace("Pool: name=","",$line) );
                } //end if
            
                if ( preg_match("((^Storage))", $line)){
                   $v[$key]['storage'] = preg_replace("/\s+\S+/","",preg_replace("/^Storage:\s+name=/","",$line));
                } //end if
            
                if ( preg_match("((^DeviceName))", $line)){
                    $v[$key]['device'] = preg_replace("/\s+\S+/","",preg_replace("/^DeviceName=/","",$line));
                } //end if            
            
                if ( preg_match("((^hour))", $line)){
                   $array_hour = explode(",", str_replace(" ", ",", str_replace("hour=","",$line) ) );
                   $hour ="";
                   foreach($array_hour as $value){
                       $hour .= str_pad($value, 2 , "0", STR_PAD_LEFT) . ", " ;
                   } // end foreach
                   $v[$key]['hour'] = substr($hour, 0, strlen($hour)-2);
                } //end if
            
                if ( preg_match("((^mins))", $line)){
                    $v[$key]['mins'] = str_pad(str_replace("mins=","",$line), 2, "0", STR_PAD_LEFT);
                    $array_hour = explode(",", $v[$key]['hour']);
                    $hour ="";
                    foreach($array_hour as $value){
                        $hour .= $value . ":" . $v[$key]['mins'] . ", ";
                    } //end foreach
                    $v[$key]['hour'] = substr($hour, 0, strlen($hour)-2);
                } //end if

                if ( preg_match("((^mday))", $line)){
                   $array_day = explode(",", str_replace(" ", ",", str_replace("mday=","",$line) ) );
                   $v[$key]['day'] = $this->sequenceShorten( $array_day, 0, 31, $array_day_short);
                } //end if            
            
                if ( preg_match("((^wday))", $line)){
                    $array_weekday = explode(",", str_replace(" ", ",", str_replace("wday=","",$line) ) );
                    $v[$key]['weekday'] = $this->sequenceShorten( $array_weekday, 0, 7, $array_weekday_long);
                } //end if

                if ( preg_match("((^wom))", $line)){
                    $array_weekmonth = explode(",", str_replace(" ", ",", str_replace("wom=","",$line) ) );
                    $v[$key]['weekmonth'] = $this->sequenceShorten( $array_weekmonth, 0, 7, $array_weekmonth_short);
                } //end if
            
                if ( preg_match("((^month))", $line)){
                    $array_month = explode(",", str_replace(" ", ",", str_replace("month=","",$line) ) );
                    $v[$key]['month'] = $this->sequenceShorten( $array_month, 0, 12, $array_month_long);
                } //end if
            
            } // end for j
            $array_schedule_options[$i]['schedule'] = $v;
            unset($v);
        }//end for i
        //echo "<pre>"; print_r($array_schedule_options); echo "</pre>"; // !!! DEBUG !!!
        //exit;

        return $array_schedule_options;
    }

}
