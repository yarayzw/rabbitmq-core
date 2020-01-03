<?php


namespace hq\mq;


class DelayConfig
{
    /**
     * @var int 触发时间
     */
    private $expiry;

    /**
     * @var string 延迟消息名称，可用于创建延迟消息通道名称
     */
    private $name;

    /**
     * @var string 处理类
     */
    private $class;

    /**
     * @var string 处理方法
     */
    private $method;

    /**
     * DelayConfig constructor.
     * @param string $name
     * @param int $expiry 延迟时间
     * @param string $class 处理类
     * @param string $method 处理方法
     */
    public function __construct(string $name,int $expiry, string $class, string $method)
    {
        $this->name = $name;
        $this->expiry = $expiry;
        $this->class = $class;
        $this->method = $method;
        $this->expiry = $expiry;
    }

    /**
     * @return int
     */
    public function getExpiry(): int
    {
        return $this->expiry * 1000;
    }

    /**
     * @param int $expiry
     */
    public function setExpiry(int $expiry): void
    {
        $this->expiry = $expiry;
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @param string $class
     */
    public function setClass(string $class): void
    {
        $this->class = $class;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @param string $method
     */
    public function setMethod(string $method): void
    {
        $this->method = $method;
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

}