<?php


namespace hq\mq;

use Exception;
use RuntimeException;

class MqConsumerConfig
{
    public const TOPIC = 'topic';
    public const DIRECT = 'direct';
    public const FANOUT = 'fanout';
    public const HEADERS = 'headers';

    /**
     * @var string
     */
    private $name;

    /**
     * @var string 交换机
     */
    private $exchange;

    /**
     * @var string 交换机类型
     */
    private $exchangeType;

    /**
     * @var string 路由
     */
    private $route;

    /**
     * @var string 列队
     */
    private $queue;

    /**
     * @var array
     */
    private $operations;

    /**
     * MqConsumerConfig constructor.
     * @param string $name
     * @param string $exchange
     * @param string $exchangeType
     * @param string $appName
     */
    public function __construct(string $name, string $exchange, string $exchangeType, string $appName)
    {
        $this->name = $name;
        $this->exchange = $exchange;
        $this->exchangeType = $exchangeType;
        $this->setRoute($exchange);
        $this->setQueue($appName);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */ 
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getExchange(): string
    {
        return $this->exchange;
    }

    /**
     * @param string $exchange
     */
    public function setExchange(string $exchange): void
    {
        $this->exchange = $exchange;
    }

    /**
     * @return string
     */
    public function getRoute(): string
    {
        return $this->route;
    }

    /**
     * @param string $exchange
     */
    public function setRoute(string $exchange): void
    {
        $this->route = $exchange . '.*';
    }

    /**
     * @return string
     */
    public function getQueue(): string
    {
        return $this->queue;
    }

    /**
     * @param string $appName
     */
    public function setQueue(string $appName): void
    {
        $this->queue = $appName . '.' . $this->name;
    }

    /**
     * @return array
     */
    public function getOperations(): array
    {
        return $this->operations;
    }

    /**
     * @return string
     */
    public function getExchangeType(): string
    {
        return $this->exchangeType;
    }

    /**
     * @param string $exchangeType
     */
    public function setExchangeType(string $exchangeType): void
    {
        $this->exchangeType = $exchangeType;
    }

    /**
     * @param array $operations
     * @throws Exception
     */
    public function setOperations(array $operations): void
    {
        foreach ($operations as $item) {
            if (isset($this->operations[$item['route']])) {
                throw new RuntimeException('路由重复！');
            }
            $queue = $item['queue'] ?? $this->queue;
            $exchangeType = $item['exchange_type'] ?? $this->exchangeType;
            $operation = new MqOperation($this->getExchange(), $exchangeType, $queue, $item['route'], $item['class'], $item['method']);

            $this->operations[$item['route']] = $operation;

        }
    }
}