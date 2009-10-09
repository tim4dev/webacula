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
 *
 * Class for convert base 64 (homebrew function)
 *
 * @package    webacula
 * @subpackage Helpers
 * @author Yuri Timofeev <tim4dev@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 */
/*
http://www.mail-archive.com/bacula-users%40lists.sourceforge.net/msg04808.html
Kern Sibbald
Fri, 23 Sep 2005 07:28:50 -0700

"gR DwABPN EHA C Pn Pn A G BAA A BC3sbr BC3moS BC3sbr A A C"

The encoding/decoding of the "attributes" or stat packet are done in:

<bacula-source>/src/findlib/attribs.c

The base64 routines are Bacula home-brew, if I remember right, so you will
need to look at that code too:

<bacula-source>/src/lib/base64.c

Unfortunately, when I wrote it, I didn't realize there was an RFC on base64
so my choice of the two special characters was different from the "standard".
This will cause non-C/C++ programmers a bit of extra work :-(
*/
/*
LStat example:
MI OfON IGk B Bk Bl A e BAA I BGkZWg BGkZWg BGkZWg A A E

negative:
A A IH/ B A A A DYutAA BAA DYut BHyM5t BHU6dJ BHU6Av A A M
*/

class MyClass_HomebrewBase64
{
    public static $base64_map = array();
    public static $base64_digits = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";

    public function homebrewBase64 ($str)
    {
        $val = $i = 0;
        $len = strlen($str);
        while (($i < $len) && ($str[$i] !== ' ')) {
            $val *= 64;
            $val += self::$base64_map[ord($str[$i])];
            $i ++;
        }
        return $val;
    }

    // create base 64 maps
    public function __construct ()
    {
        for ($i = 0; $i < 64; $i ++) {
            self::$base64_map[ord(self::$base64_digits[$i])] = $i;
        }
    }
}
