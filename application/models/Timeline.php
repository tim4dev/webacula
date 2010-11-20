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
    protected $atime;       // main array
    // graphics data
    protected $img_width;
    protected $img_height;
    protected $font_size;
    protected $font_name;
    protected $bar_count;
    protected $bar_height;
    protected $bar_space;
    protected $margin_top;  // сверху до оси X
    protected $margin_bottom; // снизу до оси X
    protected $margin_left; // слева до оси Y
    protected $margin_right;  // справа до оси X
    protected $margin_text_left; // отступ текста от начала полосы
    protected $fixfont;
    protected $bacula_acl; // bacula acl
    protected $config;




    public function __construct()
    {
        $this->db_adapter = Zend_Registry::get('DB_ADAPTER');
        $this->config = Zend_Registry::get('config');
        $this->atime = array();
        // graphics data
        $this->img_width = 780;
        $this->font_size = 10;
        $this->bar_height = ceil($this->font_size * 2);
        $this->bar_space  = ceil($this->bar_height * 0.7);
        $this->bacula_acl = new MyClass_BaculaAcl();
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
                    'JobId', 'Name', 'StartTime', 'EndTime', 'JobErrors', 'JobStatus',
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
                    'JobId', 'Name', 'StartTime', 'EndTime', 'JobErrors', 'JobStatus',
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
                    'joberrors' => 'JobErrors', 'jobstatus' => 'JobStatus',
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
				$this->atime[$i]['jobid'] = $line['jobid'];
				$this->atime[$i]['name'] = $line['name'];
    			$this->atime[$i]['h1'] = $line['h1'] + ($line['m1'] / 60);
    			$this->atime[$i]['h2'] = $line['h2'] + ($line['m2'] / 60);
    			$this->atime[$i]['flag'] = 0; // признак, что задание уложилось в сутки
    			$this->atime[$i]['start'] = $line['starttime'];
    			$this->atime[$i]['end'] = $line['endtime'];
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
                	'JobId', 'Name', 'StartTime', 'EndTime', 'JobErrors', 'JobStatus',
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
                	'JobId', 'Name', 'StartTime', 'EndTime', 'JobErrors', 'JobStatus',
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
                    'joberrors' => 'JobErrors', 'jobstatus' => 'JobStatus',
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
				$this->atime[$i]['jobid'] = $line['jobid'];
				$this->atime[$i]['name'] = $line['name'];
				$this->atime[$i]['h1'] = 0;
    			$this->atime[$i]['h2'] = $line['h2'] + ($line['m2'] / 60);
    			$this->atime[$i]['flag'] = -1; // признак, что задание началось ранее
    			$this->atime[$i]['start'] = $line['starttime'];
    			$this->atime[$i]['end'] = $line['endtime'];
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
                $select->from('Job', array('JobId', 'Name', 'StartTime', 'EndTime', 'JobErrors', 'JobStatus',
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
                $select->from('Job', array('JobId', 'Name', 'StartTime', 'EndTime', 'JobErrors', 'JobStatus',
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
                    'joberrors' => 'JobErrors', 'jobstatus' => 'JobStatus',
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
				$this->atime[$i]['jobid'] = $line['jobid'];
				$this->atime[$i]['name'] = $line['name'];
				$this->atime[$i]['h1'] = $line['h1'] + ($line['m1'] / 60);
    			$this->atime[$i]['h2'] = 23.9;
    			$this->atime[$i]['flag'] = 1; // признак, что задание окончилось позднее
    			$this->atime[$i]['start'] = $line['starttime'];
    			$this->atime[$i]['end'] = $line['endtime'];
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
                'JobId', 'Name', 'StartTime', 'EndTime', 'JobErrors', 'JobStatus',
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
                'JobId', 'Name', 'StartTime', 'EndTime', 'JobErrors', 'JobStatus',
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
                    'joberrors' => 'JobErrors', 'jobstatus' => 'JobStatus',
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
            foreach($result as $line)    {
				$this->atime[$i]['jobid'] = $line['jobid'];
				$this->atime[$i]['name'] = $line['name'];
				$this->atime[$i]['h1'] = 0;
    			$this->atime[$i]['h2'] = 23.9;
    			$this->atime[$i]['flag'] = 2; // признак, что задание началось ранее и окончилось позднее (очень длинное задание)
    			$this->atime[$i]['start'] = $line['starttime'];
    			$this->atime[$i]['end'] = $line['endtime'];
    			$i++;
			}
            $select->reset();
            unset($select);
            unset($stmt);
            // do Bacula ACLs
            $this->atime = $this->bacula_acl->doBaculaAcl( $this->atime, 'name', 'job');
        }
    }



    /**
     * Calculate image sizes, determine fonts
     *
     * @param $img_type [normal | small]
     */
    public function calculateImageData($img_type)
    {
        $this->bar_count = count($this->atime);    // кол-во полос (т.е. кол-во отображаемых Jobs)
        // fonts from .ini configuration
        if ( empty($this->config->timeline->fontname)) {
            $this->font_name = null;
        } else {
            putenv('GDFONTPATH='. $this->config->timeline->gdfontpath);
            $this->font_name = $this->config->timeline->fontname;
        }
        switch ($img_type) {
            case 'small':
                $this->font_size = 7;
                $this->img_width = 390;
                $this->bar_height = ceil($this->font_size * 1.8);  // высота одной полосы графика
                $this->bar_space  = ceil($this->bar_height * 0.4);  // расстояние м/д полосами
                $this->margin_top     = $this->bar_height + 2;  // сверху до оси X
                $this->margin_bottom  = 0; // снизу до оси X
                $this->margin_left    = 7; // слева до оси Y
                $this->margin_right   = 2;  // справа до оси X
                $this->margin_text_left = 2; // отступ текста от начала полосы
                $this->fixfont = 2; // Can be 1, 2, 3, 4, 5 for built-in fonts
            break;

            default:
                if ( empty($this->config->timeline->fontname)) {
                    $this->font_size = 10;
                } else {
                    $this->font_size = $this->config->timeline->fontsize;
                }
                $this->img_width = 780;
                $this->bar_height = ceil($this->font_size * 2);  // высота одной полосы графика
                $this->bar_space  = ceil($this->bar_height * 0.7);  // расстояние м/д полосами
                $this->margin_top     = $this->bar_height + 20;  // сверху до оси X
                $this->margin_bottom  = 60; // снизу до оси X
                $this->margin_left    = 15; // слева до оси Y
                $this->margin_right   = 2;  // справа до оси X
                $this->margin_text_left = 3; // отступ текста от начала полосы
                $this->fixfont = 4; // Can be 1, 2, 3, 4, 5 for built-in fonts
            break;
        }
        $this->img_height = $this->margin_top + $this->margin_bottom +
                            $this->bar_count * ($this->bar_height + $this->bar_space );  // Image height
    }


    /**
     * Create Timeline Image
     *
     * @param $date
     * @param $draw     if FALSE return coordinates only, for imagemap
     * @param $translate for translate
     * @param $img_type [normal | small]
     * @return image
     */
    public function createTimelineImage($date, $draw = true, $translate = null, $img_type = 'normal')
    {
        $this->GetDataTimeline($date);
        if ( empty($this->atime) )    {
            // Nothing data to graph
            return;
        }
        $this->calculateImageData($img_type);

        if ( !$draw ) $img_map = array();
        $ttf_font_error = 0;

        // созд-е пустого холста
        // Create a new true color image :
        // resource imagecreatetruecolor ( int width, int height )
        $img    = ImageCreateTrueColor($this->img_width, $this->img_height);
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
        if ($draw) ImageFilledRectangle($img, 0, 0, $this->img_width, $this->img_height, $bg_color);

        // контур фона
        // Draw a rectangle : bool imagerectangle ( resource image, int x1, int y1, int x2, int y2, int color )
        if ($draw) ImageRectangle($img, 0, 0, $this->img_width-1, $this->img_height-1, $blue);

        // --------------------------------- вычерчивание координатной сетки ---------------------------------------
        // ось X
        // Draw a line :
        // bool imageline ( resource image, int x1, int y1, int x2, int y2, int color )

        // $y0, $x0 - начало координат
        $y0 = $y2 = $this->img_height - $this->margin_bottom - $this->margin_top + $this->bar_space;
        $x0 = $this->margin_left;
        if ($draw) ImageLine($img, $x0, $y0, $this->img_width - $this->margin_right, $y2,  $blue); // ось X

        // вертикальные линии - часы
        // пунктирная линия
        $style_dash = array_merge(array_fill(0, 1, $blue), array_fill(0, 3, IMG_COLOR_TRANSPARENT));
        if ($draw) ImageSetStyle($img, $style_dash);

        $hour1 = ceil( ( $this->img_width - $x0 - $this->margin_right ) / 24 ); // шаг засечек или 1 час в пикселах
        $y2  = 0;
        for ($i = 0; $i <= 23; $i++)    {
            $x1 = $x0 + $i * $hour1;
            ImageLine($img, $x1, $y0, $x1 , $y2, IMG_COLOR_STYLED);
        }

        if ($img_type == 'normal' ) {
            // подписи к оси X
            $y1 = $this->img_height - $this->margin_bottom - $this->margin_top + $this->bar_space + $this->font_size;
            for ($i = 0; $i <= 23; $i++)    {
                // Draw a string horizontally :
                // bool imagestring ( resource image, int font, int x, int y, string sring, int color )
                // Can be 1, 2, 3, 4, 5 for built-in fonts (where higher numbers corresponding to larger fonts)

                // для учета кол-ва символов в цифрах часов
                if ( $i < 10 ) {
                    $div2 = 10;
                }   else {
                    $div2 = 5;
                }
                $x1 = $x0 - $div2 + $i * $hour1;
                if ($draw) ImageString($img, 4, $x1, $y1, sprintf("% 2d", $i), $blue);
            }

            // X axis title / название оси X
            if ( empty($this->font_name)) {
                // use system fixed font / ось подписываем встроенным шрифтом
                if ($draw) ImageString($img, $this->fixfont, floor( $this->img_width / 2 ), ( $this->img_height - floor( ($this->img_height -  $y0) / 2) ), "Hours", $blue); // do not to translate (перевод не нужен)
            } else {
                if ($draw) {
                    @ $ares = ImageTtfText($img, $this->font_size, 0, floor( $this->img_width / 2 ),
                        ( $this->img_height - floor( ($this->img_height -  $y0) / 3) ), $blue, $this->font_name, $translate->_("Hours"));
                    if ( empty($ares) ) {
                        $ttf_font_error = 1;    // TTF font not loaded/found
                        if ($draw) ImageString($img, 4, 5, 5,
                            "Font " . $this->font_name . " not loaded/found.", $black); // do not to translate (перевод не нужен)
                    }
                }
            }
        }

        //---------------- draw graph (рисуем график) --------------------------------------------
        $yt = $this->margin_top;
        $c = 0;

        for ($i = 0; $i <= $this->bar_count-1; $i++)  {
            $str = $this->atime[$i]['name'] . " (" . $this->atime[$i]['jobid'] . ")";
            // для заданий не уложившихся в сутки, рисуем знаки с определенной стороны
            switch ($this->atime[$i]['flag']) {
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
            $yr1 = $yt - ceil($this->font_size /2) - ceil($this->bar_height / 2);
            $yr2 = $yr1 + $this->bar_height;
            $xr1 = $x0 + floor( $hour1 * $this->atime[$i]['h1']);
            $xr2 = $x0 + floor( $hour1 * $this->atime[$i]['h2']);

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
            if ( (!$ttf_font_error) && (!empty($this->font_name)) ) {
                // TTF font loaded OK
                $abox = ImageTtfBbox($this->font_size, 0, $this->font_name, $str);
                $xt = $xr1 + $this->margin_text_left;
                if ( ($xt + $abox[2]) > $this->img_width )    {
                    $xt = $xr2 - $abox[2] - $this->margin_text_left;
                    if ( !$draw ) ( $xt > $xr2 ) ? $x2 = $xt : $x2 = $xr2; // coordinates for imagemap
                } else {
                    if ( !$draw ) ( ($xt + $abox[2]) > $xr2 ) ? $x2 = $xt + $abox[2] : $x2 = $xr2; // coordinates for imagemap
                }
                // draw text
                if ($draw) ImageTtfText($img, $this->font_size, 0, $xt, $yt, $text_color, $this->font_name, $str);
            }    else {
                // fix font
                $lenfix = strlen($str) * imagefontwidth($this->fixfont);
                if ( ($xr1 + $lenfix) > $this->img_width )    {
                    $xt = $xr2 - $lenfix - $this->margin_text_left;
                    if ( !$draw ) ( $xt > $xr2 ) ? $x2 = $xt : $x2 = $xr2; // coordinates for imagemap
                }   else {
                    $xt = $xr1;
                    if ( !$draw ) ( ($xt + $lenfix) > $xr2 ) ? $x2 = $xt + $lenfix : $x2 = $xr2; // coordinates for imagemap
                }
                // draw text
                if ($draw) ImageString($img, $this->fixfont, $xt, $yr1, $str, $text_color);
            }
            // save coordinates
            if ( !$draw ) {
                ($xt < $xr1) ? $x1 = $xt : $x1 = $xr1;
                $img_map[$i]['jobid'] = $this->atime[$i]['jobid'];
                $img_map[$i]['name']  = $this->atime[$i]['name'];
                $img_map[$i]['x1'] = $x1;
                $img_map[$i]['y1'] = $yr1;
                $img_map[$i]['x2'] = $x2;
                $img_map[$i]['y2'] = $yr2;
            }
            $yt = $yt + $this->bar_height + $this->bar_space;
        }
        if ($draw)  return $img;
        else return $img_map;
    }


}
