<?php


namespace dbsnOOp\Utils;


final class Logger
{

    const EMERGENCY = 7;
    const CRITICAL  = 6;
    const ERROR     = 5;
    const WARNING   = 4;
    const ALERT     = 3;
    const NOTICE    = 2;
    const INFO      = 1;
    const DEBUG     = 0;

    /**
     * @var Logger
     */
    private static $logger;

    public static $enable_level = -1;

    public static $erro_name = [
        "EMERGENCY",
        "CRITICAL",
        "WARNING",
        "ALERT",
        "NOTICE",
        "INFO",
        "DEBUG"
    ];

    public static function get()
    {
        if (!isset(self::$logger)) {
            self::$logger = new self;
        }

        return self::$logger;
    }

    public function error(string $message)
    {
        $this->write(self::ERROR, $message);
    }

    public function emergency(string $message)
    {
        $this->write(self::EMERGENCY, $message);
    }

    public function critical(string $message)
    {
        $this->write(self::CRITICAL, $message);
    }

    public function warning(string $message)
    {
        $this->write(self::WARNING, $message);
    }

    public function alert(string $message)
    {
        $this->write(self::ALERT, $message);
    }

    public function notice(string $message)
    {
        $this->write(self::NOTICE, $message);
    }

    public function info(string $message)
    {
        $this->write(self::INFO, $message);
    }

    public function debug(string $message)
    {
        $this->write(self::DEBUG, $message);
    }


    public function write(int $level = self::DEBUG, string $message)
    {
        if (!$this->enable($level)) return;
        $date = date(\DateTime::ATOM);
        $message = "[$date][dbsnoop Tracer][" . self::$erro_name[$level] . "] $message";
        error_log($message);
    }

    private function enable(int $level)
    {
        return $level >= self::$enable_level;
    }
}
