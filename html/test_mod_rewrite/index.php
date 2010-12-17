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
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
   <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
   <title>Test mod_rewrite</title>
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="expires" content="0" />
</head>
<body>

<h1 align="center">
<?php
if ( isset($_GET['testlink']) )  {
   if ( $_GET['testlink'] == 1) {
      echo "mod_rewrite test PASSED";
   }
   else {
      echo "mod_rewrite test ERROR";
   }
}  else {
      echo "mod_rewrite Test";
}
?>
</h1>

<h3 align="center"><a href="testlink1.html">Run mod_rewrite Test</a></h3>

</body>
</html>
