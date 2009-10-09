<?php
/**
 * Класс для измерения отрезков времени
 *
 * @package    webacula
 * @author Yuri Timofeev <tim4dev@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 */

class MyClass_GaugeTime
{
    protected $start_time;

    public function __construct ()
    {
        $this->start_time = $this->getTime();
    }

    /**
     * @return возвращает текущее время
     */
    function getTime ()
    {
        $ptime = explode(' ', microtime());
        $result = $ptime[1] + $ptime[0];
        return $result;
    }

    /**
     * @return возвращает разницу во времени
     */
    function diffTime ()
    {
        $end_time = $this->getTime();
        $diff_time = number_format($end_time - $this->start_time, 5);
        return $diff_time;
    }

}