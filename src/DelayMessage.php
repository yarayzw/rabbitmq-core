<?php


namespace hq\mq;


class DelayMessage
{
    /**
     * @var string 消息标识，可用于判断消息处理方法的
     */
    private $tag;

    /**
     * @var array 消息体
     */
    private $payload;

    /**
     * DelayMessage constructor.
     * @param string $tag
     * @param $payload
     */
    public function __construct(string $tag, array $payload)
    {
        $this->tag = $tag;
        $this->payload = $payload;
    }

    /**
     * @return string
     */
    public function getTag(): string
    {
        return $this->tag;
    }

    /**
     * @param string $tag
     */
    public function setTag(string $tag): void
    {
        $this->tag = $tag;
    }

    /**
     * @return array
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    /**
     * @param array $payload
     */
    public function setPayload(array $payload): void
    {
        $this->payload = $payload;
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}