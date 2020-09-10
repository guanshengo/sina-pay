<?php


namespace Guanshengo\SinaPay\Support;

use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\AbstractHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger as BaseLogger;
use Psr\Log\LoggerInterface;

class Logger
{
    /**
     * Logger instance.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * formatter.
     *
     * @var \Monolog\Formatter\FormatterInterface
     */
    protected $formatter;

    /**
     * handler.
     *
     * @var AbstractHandler
     */
    protected $handler;

    /**
     * config.
     *
     * @var array
     */
    protected $config = [
        'file' => null,
        'identify' => 'yansongda.supports',
        'level' => BaseLogger::DEBUG,
        'type' => 'daily',
        'max_files' => 30,
    ];

    /**
     * Forward call.
     *
     * @author guanshengo <me@guanshengo.cn>
     *
     * @param string $method
     * @param array  $args
     *
     * @throws Exception
     */
    public function __call($method, $args): void
    {
        call_user_func_array([$this->getLogger(), $method], $args);
    }

    /**
     * Return the logger instance.
     *
     * @param LoggerInterface $logger
     * @return $this
     */
    public function setLogger(LoggerInterface $logger): Logger
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     *
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        if (is_null($this->logger)) {
            $this->logger = $this->createLogger();
        }

        return $this->logger;
    }

    /**
     * Make a default log instance.
     * @return BaseLogger
     */
    public function createLogger(): BaseLogger
    {
        $handler = $this->getHandler();

        $handler->setFormatter($this->getFormatter());

        $logger = new BaseLogger($this->config['identify']);

        $logger->pushHandler($handler);

        return $logger;
    }

    /**
     * getFormatter
     *
     * @param FormatterInterface $formatter
     * @return $this
     */
    public function setFormatter(FormatterInterface $formatter): self
    {
        $this->formatter = $formatter;

        return $this;
    }

    /**
     * getFormatter
     *
     * @return FormatterInterface
     */
    public function getFormatter(): FormatterInterface
    {
        if (is_null($this->formatter)) {
            $this->formatter = $this->createFormatter();
        }

        return $this->formatter;
    }
    /**
     * createFormatter
     *
     * @return LineFormatter
     */
    public function createFormatter(): LineFormatter
    {
        return new LineFormatter(
            "%datetime% > %channel%.%level_name% > %message% %context% %extra%\n\n",
            null,
            false,
            true
        );
    }

    /**
     * setHandler
     *
     * @param AbstractHandler $handler
     * @return $this
     */
    public function setHandler(AbstractHandler $handler): self
    {
        $this->handler = $handler;

        return $this;
    }

    /**
     * getHandler
     *
     * @return AbstractHandler
     */
    public function getHandler(): AbstractHandler
    {
        if (is_null($this->handler)) {
            $this->handler = $this->createHandler();
        }

        return $this->handler;
    }

    /**
     * createHandler
     *
     * @return AbstractHandler
     */
    public function createHandler(): AbstractHandler
    {
        $file = $this->config['file'] ?? sys_get_temp_dir().'/logs/'.$this->config['identify'].'.log';

        if ('single' === $this->config['type']) {
            return new StreamHandler($file, $this->config['level']);
        }

        return new RotatingFileHandler($file, $this->config['max_files'], $this->config['level']);
    }

    /**
     * setConfig
     * @param array $config
     * @return $this
     */
    public function setConfig(array $config): self
    {
        $this->config = array_merge($this->config, $config);

        return $this;
    }

    /**
     * getConfig
     *
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}