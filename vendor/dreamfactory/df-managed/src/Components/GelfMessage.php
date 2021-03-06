<?php namespace DreamFactory\Managed\Components;

use DreamFactory\Managed\Enums\AuditLevels;

/**
 * A GELF v1.1 message
 * v1.1 (11/2013)
 *
 * @link http://www.graylog2.org/resources/gelf/specification
 */
class GelfMessage
{
    //******************************************************************************
    //* Constants
    //******************************************************************************

    /**
     * @type string The GELF version of this message
     */
    const GELF_VERSION = '1.1';

    //**********************************************************************
    //* Members
    //**********************************************************************

    /**
     * @type string
     */
    protected $version = self::GELF_VERSION;
    /**
     * @type string
     */
    protected $host;
    /**
     * @type string
     */
    protected $shortMessage;
    /**
     * @type string
     */
    protected $fullMessage;
    /**
     * @type double
     */
    protected $timestamp;
    /**
     * @type int
     */
    protected $level = AuditLevels::INFO;
    /**
     * @type array Additional fields added to the message
     */
    protected $additional = [];

    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * @param array $additional Any additional fields data to send in the message
     */
    public function __construct(array $additional = [])
    {
        $this->reset()->addAdditionalFields($additional);
    }

    //**********************************************************************
    //* Public Methods
    //**********************************************************************

    /**
     * Resets the message to default values
     *
     * @return $this
     */
    public function reset()
    {
        /** @var array $argv */
        global $argv;

        $this->host = gethostname();
        $this->timestamp = microtime(true);
        $this->level = AuditLevels::INFO;
        $this->shortMessage = $this->fullMessage = null;
        $this->additional = [];

        $_file = null;

        switch ($this->additional['_php_sapi'] = PHP_SAPI) {
            case 'cli':
                isset($argv[0]) && ($_file = $argv[0]);
                break;

            default:
                isset($_SERVER, $_SERVER['SCRIPT_FILENAME']) && ($_file = $_SERVER['SCRIPT_FILENAME']);
                break;
        }

        $_file && ($this->additional['_file'] = $_file);

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $_message = [
            'version'       => $this->version,
            'host'          => $this->host,
            'short_message' => $this->shortMessage,
            'full_message'  => $this->fullMessage,
            'timestamp'     => $this->timestamp,
            'level'         => $this->level,
        ];

        if (!empty($this->additional)) {
            $_message = array_merge($_message, $this->additional);
        }

        return $_message;
    }

    /**
     * @param string $name The name of the additional field
     *
     * @throws \InvalidArgumentException
     * @return string
     */
    public function getAdditionalField($name)
    {
        $_key = '_' . ltrim($name, '_');

        return array_key_exists($_key, $this->additional) ? $this->additional[$_key] : null;
    }

    /**
     * @param string $name  The name of the additional field
     * @param string $value The value of the additional field; null to unset
     *
     * @throws \InvalidArgumentException
     * @return $this
     */
    public function setAdditionalField($name, $value)
    {
        $_key = '_' . ltrim($name, '_');

        if ('_id' == $_key || '_key' == $_key) {
            throw new \InvalidArgumentException('Additional fields may not be called "id" or "key".');
        };

        if (null === $value && array_key_exists($_key, $this->additional)) {
            unset($this->additional[$_key]);
        } else {
            $this->additional[$_key] = $value;
        }

        return $this;
    }

    /**
     * Adds an array of additional fields at once
     *
     * @param array $fields Array of key value pairs
     *
     * @return $this
     */
    public function addAdditionalFields(array $fields = [])
    {
        foreach ($fields as $_key => $_value) {
            $this->setAdditionalField($_key, $_value);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return string
     */
    public function getFullMessage()
    {
        return $this->fullMessage;
    }

    /**
     * @param string $fullMessage
     *
     * @return $this
     */
    public function setFullMessage($fullMessage)
    {
        $this->fullMessage = $fullMessage;

        return $this;
    }

    /**
     * @return string
     */
    public function getShortMessage()
    {
        return $this->shortMessage;
    }

    /**
     * @param string $shortMessage
     *
     * @return GelfMessage
     */
    public function setShortMessage($shortMessage)
    {
        $this->shortMessage = $shortMessage;

        return $this;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return double
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @return integer
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * @param int $level The message level; null for default
     *
     * @throws \InvalidArgumentException
     * @return $this
     */
    public function setLevel($level = AuditLevels::INFO)
    {
        if (!AuditLevels::contains($level)) {
            throw new \InvalidArgumentException('The level "' . $level . '" is not valid.');
        }

        $this->level = $level;

        return $this;
    }
}
