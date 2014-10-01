<?php
/**
 * Copyright 2009, 2010, 2011, 2014 Yuriy Timofeev <tim4dev@gmail.com>
 * @author Yuriy Timofeev <tim4dev@gmail.com>
 * @package webacula
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 */


class ChartControllerTest extends ControllerTestCase
{

    /**
     * @group chart
     */
    public function testTimeline ()
    {
        print "\n" . __METHOD__ . ' ';
        $this->_rootLogin();
        $name_img = 'img_tmp.png';
        if (file_exists($name_img)) {
            unlink($name_img);
        }
        $this->assertTrue(function_exists("imagepng"), "(imagepng not found)");
        $this->dispatch('chart/timeline/datetimeline/' . date("Y-m-d", time()));
        $img = $this->response->outputBody();
        $this->_isLogged($img);
        $this->assertModule('default');
        $this->assertController('chart');
        $this->assertAction('timeline');
        if (empty($img)) {
            $this->assertTrue(FALSE, "image is empty!");
        }
        $f = fopen("$name_img", 'w');
        $res = fwrite($f, $img);
        if (! $res) {
            $this->assertTrue(FALSE, "file $name_img can't writing!");
        }
        fclose($f);
        $this->assertNotNull($size = GetImageSize($name_img));
        $this->assertGreaterThan(700, $size[0]); // width
        $this->assertGreaterThan(400, $size[1]); // height
        unlink($name_img);
        $this->assertFileNotExists($name_img, "file $name_img not deleted!");
    }
}
