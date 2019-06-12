<?php

namespace chemezov\yii2\yandex\cloud;

use Aws\S3\S3Client;
use chemezov\yii2\yandex\cloud\handlers\PlainCommandHandler;
use chemezov\yii2\yandex\cloud\interfaces;
use yii\base\Configurable;
use yii\base\Exception;

/**
 * Class HandlerResolver
 *
 * @package chemezov\yii2\yandex\cloud
 */
class HandlerResolver implements interfaces\HandlerResolver, Configurable
{
    /** @var array */
    protected $handlers = [];

    /** @var string */
    protected $plainCommandHandlerClassName = PlainCommandHandler::class;

    /** @var \Aws\S3\S3Client */
    protected $s3Client;

    /**
     * HandlerResolver constructor.
     *
     * @param \Aws\S3\S3Client $s3Client
     * @param array            $config name-value pairs that will be used to initialize the object properties
     */
    public function __construct(S3Client $s3Client, array $config = [])
    {
        $this->configure($config);
        $this->s3Client = $s3Client;
    }

    /**
     * @param array $properties
     *
     * @return void
     */
    private function configure(array $properties)
    {
        foreach ($properties as $name => $value) {
            $this->{$name} = $value;
        }
    }

    /**
     * @param \chemezov\yii2\yandex\cloud\interfaces\commands\Command $command
     *
     * @return \chemezov\yii2\yandex\cloud\interfaces\handlers\Handler
     * @throws \yii\base\Exception
     */
    public function resolve(interfaces\commands\Command $command): interfaces\handlers\Handler
    {
        $commandClass = get_class($command);

        if (isset($this->handlers[$commandClass])) {
            $handler = $this->handlers[$commandClass];

            return is_object($handler) ? $handler : $this->createHandler($handler);
        }

        if ($command instanceof interfaces\commands\PlainCommand) {
            return $this->createHandler($this->plainCommandHandlerClassName);
        }

        $handlerClass = $commandClass . 'Handler';
        if (class_exists($handlerClass)) {
            return $this->createHandler($handlerClass);
        }

        $handlerClass = str_replace('\\commands\\', '\\handlers\\', $handlerClass);
        if (class_exists($handlerClass)) {
            return $this->createHandler($handlerClass);
        }

        throw new Exception("Could not terminate the handler of a command of type \"{$commandClass}\"");
    }

    /**
     * @param string $commandClass
     * @param mixed  $handler
     */
    public function bindHandler(string $commandClass, $handler)
    {
        $this->handlers[$commandClass] = $handler;
    }

    /**
     * @param array $handlers
     */
    public function setHandlers(array $handlers)
    {
        foreach ($handlers as $commandClass => $handler) {
            $this->bindHandler($commandClass, $handler);
        }
    }

    /**
     * @param string $className
     */
    public function setPlainCommandHandler(string $className)
    {
        $this->plainCommandHandlerClassName = $className;
    }

    /**
     * @param string|array $type
     *
     * @return \chemezov\yii2\yandex\cloud\interfaces\handlers\Handler
     * @throws \yii\base\InvalidConfigException
     */
    protected function createHandler($type): interfaces\handlers\Handler
    {
        return \Yii::createObject($type, [$this->s3Client]);
    }
}
