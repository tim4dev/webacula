<?php
/**
 * Copyright 2009 Yuri Timofeev tim4dev@gmail.com
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

class Director
{

	public $config;	
	public $sudo;
	public $bconsole;
	public $bconsolecmd;


	public function __construct()
	{
		$this->config      = Zend_Registry::get('config');
		$this->sudo 	   = $this->config->bacula->sudo;
		$this->bconsole    = $this->config->bacula->bconsole;
		$this->bconsolecmd = $this->config->bacula->bconsolecmd;
		$cmd = '';
        if ( isset($this->sudo))	{
            // run with sudo
            $cmd = $this->sudo . ' ' . $this->bconsole . ' ' . $this->bconsolecmd;
        } else {
            $cmd = $this->bconsole . ' ' . $this->bconsolecmd;
        }
        $this->bconsolecmd = $cmd;      
	}


	public function execDirector($cmd)
	{
		$command_output = '';
		$return_var = 0;
		$result_error = '';
        exec($this->bconsolecmd . ' ' . $cmd, $command_output, $return_var);
        // check return status of the executed command
        if ( $return_var != 0 )	{
			$result_error = 'ERROR_BCONSOLE';
		}
		return(array('command_output' => $command_output, 'result_error' => $result_error, 'return_var' => $return_var));
	}    	    
	
	public function isFoundBconsole()
	{
		if ( file_exists($this->bconsole))	{
    		return TRUE;
		} else {
			return FALSE;
		}
	}

}