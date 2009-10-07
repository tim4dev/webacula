<?php
/**
 * @package    webacula
 * @author Yuri Timofeev <tim4dev@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 */

class MyClass_SendEmail {

    public function mySendEmail($from, $to, $body, $subj)
    {
        Zend_Loader::loadClass('Zend_Mail');
        $mail = new Zend_Mail('utf-8');
        $mail->addHeader('X-MailGenerator', 'webacula');
        $mail->setBodyText($body, 'UTF-8');
        $mail->setFrom($from, 'Webacula Logbook');
        $mail->addTo($to, 'Bacula admin');
        $mail->setSubject($subj);
        $mail->send();
    }
}