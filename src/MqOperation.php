<?php
/**
 * Created by PhpStorm.
 * User: jiang
 * Date: 2019/4/11
 * Time: 20:55
 */

namespace hq\mq;


class MqOperation
{
    /**
     * @var string 交换机
     */
    private $exchange;

    /**
     * @var string 交换机类型
     */
    private $exchangeType;

    /**
     * @var string 通道
     */
    private $queue;

    /**
     * @var string 路由
     */
    private $route;

    /**
     * @var string 处理的类
     */
    private $class;

    /**
     * @var string 处理的方法
     */
    private $method;

    /**
     * MqOperation constructor.
     * @param $exchange
     * @param $exchangeType
     * @param $queue
     * @param $route
     * @param $class
     * @param $method
     */
    public function __construct(string $exchange, string $exchangeType, string $queue, string $route, string $class, string $method)
    {
        $this->exchange = $exchange;
        $this->exchangeType = $exchangeType;
        $this->queue = $queue;
        $this->route = $route;
        $this->class = $class;
        $this->method = $method;
    }

    /**
     * @return mixed
     */
    public function getExchange(): string
    {
        return $this->exchange;
    }

    /**
     * @param mixed $exchange
     */
    public function setExchange($exchange): void
    {
        $this->exchange = $exchange;
    }

    /**
     * @return mixed
     */
    public function getQueue(): string
    {
        return $this->queue;
    }

    /**
     * @param mixed $queue
     */
    public function setQueue($queue): void
    {
        $this->queue = $queue;
    }

    /**
     * @return mixed
     */
    public function getRoute(): string
    {
        return $this->route;
    }

    /**
     * @param mixed $route
     */
    public function setRoute($route): void
    {
        $this->route = $route;
    }

    /**
     * @return mixed
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @param mixed $class
     */
    public function setClass($class): void
    {
        $this->class = $class;
    }

    /**
     * @return mixed
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @param mixed $method
     */
    public function setMethod($method): void
    {
        $this->method = $method;
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
}