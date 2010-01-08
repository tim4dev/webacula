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
 *
 * Class for get data for graph timeline Job
 *
 * @package    webacula
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
        $atime = array();
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


    /**
     * Create Timeline Image
     *
     * @param $atime    data for timeline
     * @param $fontname
     * @param $fontsize
     * @return image
     */
    public function createTimelineImage($date, $draw = true)
    {
        $atime = $this->GetDataTimeline($date);
        if ( empty($atime) )    {
            // Nothing data to graph
            return;
        }
        // fonts from .ini configuration
        $config = new Zend_Config_Ini('../application/config.ini', 'timeline');
        if ( empty($config->fontname)) {
            $fontname = null;
            $fontsize = 10;
        } else {
            putenv('GDFONTPATH='. $config->gdfontpath);
            $fontname = $config->fontname;
            $fontsize = $config->fontsize;
        }

        if ( !$draw ) $img_map = array();
        $ttf_font_error = 0;
        // calculate values
        $height_bar = ceil($fontsize * 2);  // высота одной полосы графика
        $space_bar  = ceil($height_bar * 0.7);  // расстояние м/д полосами

        $margin_top     = 40;  // сверху до оси X
        $margin_bottom  = 60; // снизу до оси X
        $margin_left    = 15; // слева до оси Y
        $margin_right   = 2;  // справа до оси X
        $margin_text_left = 3; // отступ текста от начала полосы
        $count_bar  = count($atime);    // кол-во полос (т.е. кол-во отображаемых Jobs)

        $height = $margin_top + $margin_bottom + $count_bar * ($height_bar + $space_bar );  // Image height
        $width  = 780;  // Image width

        $fixfont = 4; // Can be 1, 2, 3, 4, 5 for built-in fonts

        // созд-е пустого холста
        // Create a new true color image :
        // resource imagecreatetruecolor ( int width, int height )
        $img    = ImageCreateTrueColor($width, $height);
        if (!$img)  {
            // Handle the error
            $this->view->result = null;
            $this->getResponse()->setHeader('Content-Type', 'text/html; charset=utf-8');
            throw new Zend_Exception('Internal ERROR: ImageCreateTrueColor');
            return;
        }
        // цвета
        $white = ImageColorAllocate($img, 255, 255, 255);
        $black = ImageColorAllocate($img, 0, 0, 0);
        $blue  = ImageColorAllocate($img, 0x49, 0x74, 0xBC0);
        // массив цветов для полос
        $acolor = array(
            ImageColorAllocate($img, 0xEA, 0xEA, 0x33), // yellow
            ImageColorAllocate($img, 0xFF, 0xBA, 0xBA), // red
            ImageColorAllocate($img, 0xD0, 0xAE, 0xFF), // blue
            ImageColorAllocate($img, 0x9D, 0xED, 0x00), // green
            ImageColorAllocate($img, 0xDC, 0xDC, 0xDC)  // gray
        );
        $acolor_count = count($acolor); // кол-во цветов для рисования полос

        $bg_color   = $white;
        $text_color = $black;

        // создание фона для рисования
        // Draw a filled rectangle : bool imagefilledrectangle ( resource image, int x1, int y1, int x2, int y2, int color )
        if ($draw) ImageFilledRectangle($img, 0, 0, $width, $height, $bg_color);

        // контур фона
        // Draw a rectangle : bool imagerectangle ( resource image, int x1, int y1, int x2, int y2, int color )
        if ($draw) ImageRectangle($img, 0, 0, $width-1, $height-1, $blue);

        // --------------------------------- вычерчивание координатной сетки ---------------------------------------
        // ось X
        // Draw a line :
        // bool imageline ( resource image, int x1, int y1, int x2, int y2, int color )

        // $y0, $x0 - начало координат
        $y0 = $y2 = $height - $margin_bottom - $margin_top + $space_bar;
        $x0 = $margin_left;
        if ($draw) ImageLine($img, $x0, $y0, $width - $margin_right, $y2,  $blue); // ось X

        // вертикальные линии - часы
        // пунктирная линия
        $style_dash = array_merge(array_fill(0, 1, $blue), array_fill(0, 3, IMG_COLOR_TRANSPARENT));
        if ($draw) ImageSetStyle($img, $style_dash);

        $hour1 = ceil( ( $width - $x0 - $margin_right ) / 24 ); // шаг засечек или 1 час в пикселах
        $y2  = 0;
        for ($i = 0; $i <= 23; $i++)    {
            $x1 = $x0 + $i * $hour1;
            ImageLine($img, $x1, $y0, $x1 , $y2, IMG_COLOR_STYLED);
        }

        // подписи к оси X
        $y1 = $height - $margin_bottom - $margin_top + $space_bar + $fontsize;
        for ($i = 0; $i <= 23; $i++)    {
            // Draw a string horizontally :
            // bool imagestring ( resource image, int font, int x, int y, string sring, int color )
            // Can be 1, 2, 3, 4, 5 for built-in fonts (where higher numbers corresponding to larger fonts)

            // для учета кол-ва сиволов в цифрах часов
            if ( $i < 10 ) {
                $div2 = 10;
            }   else {
                $div2 = 5;
            }
            $x1 = $x0 - $div2 + $i * $hour1;
            if ($draw) ImageString($img, 4, $x1, $y1, sprintf("% 2d", $i), $blue);
        }

        // X axis title / название оси X
        if ( empty($config->fontname)) {
            // use system fixed font / ось подписываем встроенным шрифтом
            if ($draw) ImageString($img, $fixfont, floor( $width / 2 ), ( $height - floor( ($height -  $y0) / 2) ), "Hours", $blue); // do not to translate (перевод не нужен)
        } else {
            @ $ares = ImageTtfText($img, $fontsize, 0, floor( $width / 2 ),
                ( $height - floor( ($height -  $y0) / 3) ), $blue, $fontname, $this->view->translate->_("Hours"));
            if ( empty($ares) ) {
                $ttf_font_error = 1;    // TTF font not loaded/found
                ImageString($img, 4, 5, 5,
                    "Font " . $fontname . " not loaded/found.", $black); // do not to translate (перевод не нужен)
            }
        }

        //---------------- draw graph (рисуем график) --------------------------------------------
        $yt = $margin_top;
        $c = 0;

        for ($i = 0; $i <= $count_bar-1; $i++)  {
            $str = $atime[$i]['name'] . " (" . $atime[$i]['jobid'] . ")";
            // для заданий не уложившихся в сутки, рисуем знаки с определенной стороны
            switch ($atime[$i]['flag']) {
                case -1:
                    $str = '<--' . $str;    // задание началось ранее
                break;
                case 1:
                    $str = $str . '-->';    // задание закончилось позднее
                break;
                case 2:
                    $str = '<--' . $str . '-->';    // задание началось ранее и закончилось позднее (очень длинное задание)
                break;
            }

            // Draw a filled rectangle:
            // bool imagefilledrectangle ( resource image, int x1, int y1, int x2, int y2, int color )

            // полосы
            $yr1 = $yt - ceil($fontsize /2) - ceil($height_bar / 2);
            $yr2 = $yr1 + $height_bar;
            $xr1 = $x0 + floor( $hour1 * $atime[$i]['h1']);
            $xr2 = $x0 + floor( $hour1 * $atime[$i]['h2']);

            // если слишком маленькая полоса
            if ( ($xr2 - $xr1) < 3 )    {
                $xr2 = $xr1 + 3;
            }

            // цвет
            if ( $c > $acolor_count-1 ) {
                $c = 0;
            }
            // draw restangle
            if ($draw) ImageFilledRectangle( $img, $xr1, $yr1, $xr2, $yr2, $acolor[$c++] );

            // Write text to the image using TrueType fonts :
            // array imagettftext ( resource image, float size, float angle, int x, int y, int color, string fontfile, string text )
            // x - The coordinates given by x and y will define the basepoint of the first character
            // (roughly the lower-left corner of the character).
            // This is different from the imagestring(), where x and y define the upper-left corner of the first character.
            // For example, "top left" is 0, 0.
            // size - The font size. Depending on your version of GD, this should be specified as the pixel size (GD1) or point size (GD2)

            // **************** text *****************
            // array imagettfbbox ( float size, float angle, string fontfile, string text )
            // где расположить текст
            // расчет координат текста
            // левая координата X = $abox[0], правая X = $abox[2]
            if ( (!$ttf_font_error) && (!empty($config->fontname)) ) {
                // TTF font loaded OK
                $abox = ImageTtfBbox($fontsize, 0, $fontname, $str);
                $xt = $xr1 + $margin_text_left;
                if ( ($xt + $abox[2]) > $width )    {
                    $xt = $xr2 - $abox[2] - $margin_text_left;
                }
                // draw text
                if ($draw) ImageTtfText($img, $fontsize, 0, $xt, $yt, $text_color, $fontname, $str);
            }    else {
                // fix font
                $lenfix = strlen($str) * imagefontwidth($fixfont);
                if ( ($xr1 + $lenfix) > $width )    {
                    $xt = $xr2 - $lenfix - $margin_text_left;
                }   else {
                    $xt = $xr1;
                }
                // draw text
                if ($draw) ImageString($img, $fixfont, $xt, $yr1, $str, $text_color);
            }
            // save coordinates
            if ( !$draw ) {
                ($xt < $xr1) ? $x1 = $xt : $x1 = $xr1;
                ($xt > $xr2) ? $x2 = $xt : $x2 = $xr2;
                $img_map[$i]['jobid'] = $atime[$i]['jobid'];
                $img_map[$i]['name']  = $atime[$i]['name'];
                $img_map[$i]['x1'] = $x1;
                $img_map[$i]['y1'] = $yr1;
                $img_map[$i]['x2'] = $x2;
                $img_map[$i]['y2'] = $yr2;
            }
            $yt = $yt + $height_bar + $space_bar;
        }
        if ($draw)  return $img;
        else return $img_map;
    }


}
