<?php

require_once 'Zend/Validate/Interface.php';

class MyClass_Validate_Datetime implements Zend_Validate_Interface
{
    /**
     * Array of validation failure messages
     *
     * @var array
     */
    protected $_messages = array();

    /**
     * Defined by Zend_Validate_Interface
     *
     * Returns true if and only if $value is a valid date of the format YYYY-MM-DD
     *
     * @param  string $value
     * @return boolean
     */
    public function isValid($value)
    {
        $this->_messages = array();

        $valueString = (string) $value;

        if (!preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $valueString)) {
            $this->_messages[] = "'$valueString' is not of the format YYYY-MM-DD HH:MM:SS";
            return false;
        }

        list($year, $month, $day) = sscanf($valueString, '%d-%d-%d');

        if (!checkdate($month, $day, $year)) {
            $this->_messages[] = "'$valueString' does not appear to be a valid date";
            return false;
        }

        return true;
    }

    /**
     * Defined by Zend_Validate_Interface
     *
     * Returns array of validation failure messages
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->_messages;
    }

    public function getErrors()
    {
        return $this->_messages;
    }

}
