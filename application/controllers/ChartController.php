<?php
/**
 *
 * Copyright 2007, 2008 Yuri Timofeev tim4dev@gmail.com
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
 * @author Yuri Timofeev <tim4dev@gmail.com>
 * @package webacula
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 *
 */

/* Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';

class ChartController extends Zend_Controller_Action
{

    function init()
    {
        // Disable view script autorendering
        $this->_helper->viewRenderer->setNoRender();

        $this->translate = Zend_Registry::get('translate');

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
        // for debug !!!
        /*Zend_Loader::loadClass('Zend_Log_Writer_Stream');
        Zend_Loader::loadClass('Zend_Log');
        $writer = new Zend_Log_Writer_Stream('/tmp/timeline.log');
        $this->logger = new Zend_Log($writer);
        $this->logger->log("debug on", Zend_Log::INFO);*/
    }


    /**
     * Create Image for Timeline of Jobs
     *
     * @return image
     */
    function timelineAction()
    {
        $test = $this->_request->getParam('test');
        if ( empty($test) )
        {
            // not test : disable layouts
            $this->_helper->layout->disableLayout();
        }
        // http://localhost/webacula/chart/timeline/datetimeline/2009-06-10
        // check GD lib (php-gd)
        if ( !extension_loaded('gd') ) {
            // No GD lib (php-gd) found
            $this->view->result = null;
            $this->getResponse()->setHeader('Content-Type', 'text/html; charset=utf-8');
            throw new Zend_Exception($this->translate->_('ERROR: The GD extension isn`t loaded. Please install php-gd package.'));
            return;
        }
        $this->input->setData( array('datetimeline' => $this->_request->getParam('datetimeline')) );
        if ( $this->input->isValid() ) {
            $date = $this->input->getEscaped('datetimeline');
        } else {
            $this->view->result = null;
            return;
        }

        if ( empty($date)  )	{
            // Nothing data to graph
            return;
        }

        $timeline = new Timeline;
        $atime = $timeline->GetDataTimeline($date);
        if ( empty($atime) )	{
            // Nothing data to graph
            return;
        }

        // fonts from .ini configuration
        $config = new Zend_Config_Ini('../application/config.ini', 'timeline');

        putenv('GDFONTPATH='. $config->gdfontpath);
        $fontname = $config->fontname;
        $fontsize = $config->fontsize;
        //$this->logger->log("timelineAction() : $date\n$fontname\n$fontsize\n", Zend_Log::INFO); // !!! debug

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
        if (!$img)	{
            // TODO: Handle the error
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

        $bg_color   = $white;//ImageColorAllocate($img, 0xCF, 0xEF, 0xB7);
        $text_color = $black;

        // создание фона для рисования
        // Draw a filled rectangle : bool imagefilledrectangle ( resource image, int x1, int y1, int x2, int y2, int color )
        ImageFilledRectangle($img, 0, 0, $width, $height, $bg_color);

        // контур фона
        // Draw a rectangle : bool imagerectangle ( resource image, int x1, int y1, int x2, int y2, int color )
        ImageRectangle($img, 0, 0, $width-1, $height-1, $blue);

        // --------------------------------- вычерчивание координатной сетки ---------------------------------------
        // ось X
        // Draw a line :
        // bool imageline ( resource image, int x1, int y1, int x2, int y2, int color )

        // $y0, $x0 - начало координат
        $y0 = $y2 = $height - $margin_bottom - $margin_top + $space_bar;
        $x0 = $margin_left;
        ImageLine($img, $x0, $y0, $width - $margin_right, $y2,  $blue); // ось X

        // вертикальные линии - часы
        // пунктирная линия
        $style_dash = array_merge(array_fill(0, 1, $blue), array_fill(0, 3, IMG_COLOR_TRANSPARENT));
        ImageSetStyle($img, $style_dash);

        $hour1 = ceil( ( $width - $x0 - $margin_right ) / 24 ); // шаг засечек или 1 час в пикселах
        $y2  = 0;
        for ($i = 0; $i <= 23; $i++)	{
            $x1 = $x0 + $i * $hour1;
            ImageLine($img, $x1, $y0, $x1 , $y2, IMG_COLOR_STYLED);
        }

        // подписи к оси X
        $y1 = $height - $margin_bottom - $margin_top + $space_bar + $fontsize;
        for ($i = 0; $i <= 23; $i++)	{
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
            ImageString($img, 4, $x1, $y1, sprintf("% 2d", $i), $blue);
        }

        // название оси X
        @ $ares = ImageTtfText($img, $fontsize, 0, floor( $width / 2 ), ( $height - floor( ($height -  $y0) / 3) ), $blue, $fontname, $this->translate->_("Hours"));
        if ( empty($ares) )	{
            $ttf_font_error = 1;	// TTF font not loaded/found
            ImageString($img, 4, 5, 5, "Font " . $fontname . " not loaded/found.", $black); // do not to translate (перевод не нужен)
            // ось подписываем встроенным шрифтом
            ImageString($img, $fixfont, floor( $width / 2 ), ( $height - floor( ($height -  $y0) / 2) ), "Hours", $blue); // do not to translate (перевод не нужен)
        } else {
            $ttf_font_error = 0;
        }

        //---------------- draw graph (рисуем график) --------------------------------------------
        $yt = $margin_top;
        $c = 0;

        for ($i = 0; $i <= $count_bar-1; $i++)	{
            $str = $atime[$i]['name'] . " (" . $atime[$i]['jobid'] . ")";
            // для заданий не уложившихся в сутки, рисуем знаки с определенной стороны
            switch ($atime[$i]['flag']) {
                case -1:
                    $str = "<--" . $str;	// задание началось ранее
                break;
                case 1:
                    $str = $str . "-->";	// задание закончилось позднее
                break;
                case 2:
                    $str = "<--" . $str . "-->";	// задание началось ранее и закончилось позднее (очень длинное задание)
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
            if ( ($xr2 - $xr1) < 3 ) 	{
                $xr2 = $xr1 + 3;
            }

            // цвет
            if ( $c > $acolor_count-1 ) {
                $c = 0;
            }
            ImageFilledRectangle( $img, $xr1, $yr1, $xr2, $yr2, $acolor[$c++] );

            // Write text to the image using TrueType fonts :
            // array imagettftext ( resource image, float size, float angle, int x, int y, int color, string fontfile, string text )
            // x - The coordinates given by x and y will define the basepoint of the first character
            // (roughly the lower-left corner of the character).
            // This is different from the imagestring(), where x and y define the upper-left corner of the first character.
            // For example, "top left" is 0, 0.
            // size - The font size. Depending on your version of GD, this should be specified as the pixel size (GD1) or point size (GD2)

            // **************** текст *****************
            // array imagettfbbox ( float size, float angle, string fontfile, string text )
            // где расположить текст
            // расчет координат текста
            // левая координата X = $abox[0], правая X = $abox[2]
            if ( !$ttf_font_error )	{
                // TTF font loaded OK
                $abox = ImageTtfBbox($fontsize, 0, $fontname, $str);
                $xt = $xr1 + $margin_text_left;
                if ( ($xt + $abox[2]) > $width )	{
                    $xt = $xr2 - $abox[2] - $margin_text_left;
                }

                // -------------- пишем текст
                ImageTtfText($img, $fontsize, 0, $xt, $yt, $text_color, $fontname, $str);
            }    else {
                $lenfix = strlen($str) * imagefontwidth($fixfont);
                if ( ($xr1 + $lenfix) > $width )	{
                    $xt = $xr2 - $lenfix - $margin_text_left;
                }   else {
                    $xt = $xr1;
                }
                ImageString($img, $fixfont, $xt, $yr1, $str, $text_color);
            }
            $yt = $yt + $height_bar + $space_bar;
        }

        // Set the headers
        $this->getResponse()->setHeader('Content-Type', 'image/png');
        // Output a PNG image to either the browser or a file :
        // bool imagepng ( resource image [, string filename [, int quality [, int filters]]] )
        $res = imagepng($img, null, 7);
        //$this->logger->log("timelineAction() : $res", Zend_Log::INFO); // !!! debug
        //imagepng($img, '/tmp/timeline.png'); // !!! debug

        // Destroy an image resource
        if ($res) imagedestroy($img);
    }

}