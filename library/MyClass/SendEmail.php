<?php
/**
 * @package    webacula
 * @author Yuri Timofeev <tim4dev@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 */

class MyClass_SendEmail {

    /**
     * $from_email, $from_name, $to_email, $to_name, $subj, $body
     *
     * @param <type> $from_email
     * @param <type> $from_name
     * @param <type> $to_email
     * @param <type> $to_name
     * @param <type> $body
     * @param <type> $subj
     */
    public function mySendEmail(
            $from_email,
            $from_name = 'Webacula',
            $to_email,
            $to_name   = '',
            $subj,
            $body )
    {
        Zend_Loader::loadClass('Zend_Mail');
        $mail = new Zend_Mail('utf-8');
        $mail->addHeader('X-MailGenerator', 'webacula');
        $mail->setBodyText($body, 'UTF-8');
        $mail->setFrom($from_email, $from_name);
        $mail->addTo($to_email,     $to_name);
        $mail->setSubject($subj);
        return $mail->send();
    }
}