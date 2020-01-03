<?php


namespace hq\mq;

use ErrorException;
use Exception;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class Mq
{
    public const CACHE_EXCHANGE = 'cache';

    private $config = array(
        'host' => '127.0.0.1',
        'port' => '5672',
        'user' => 'guest',
        'password' => 'guest',
        'vhost' => '/',

        'exchange_type' => 'topic',     //默认topic类型
        'exchange_key' => '',

        'is_delay' => false,        //是否需要开启延迟队列
        'pre_exchange' => '',

        'passive' => false,     //查询某一个队列是否已存在，如果不存在，不想建立该队列
        'durable' => true,      //是否持久化
        'auto_delete' => false, //是否自动删除

        'exclusive' => false,   //队列的排他性
        'no_local' => false,
        'no_ack' => false,       //是否需不需要应答
        'nowait' => false,      //该方法需要应答确认
        'consumer_tag' => ''

    );

    /**
     * @var string
     */
    public $appName = '';       //应用名称，作为交换机前缀

    private $isDelay = false;   //默认不开启延迟队列

    private $preExchange = '';  //交换机前缀

    public const ALLOW_EXCHANGE_TYPE = ['topic', 'direct', 'fanout', 'header'];

    /**
     * @var AMQPStreamConnection 连接
     */
    private $connection;

    /**
     * @var AMQPChannel 消息通道
     */
    private $channel;

    /**
     * Mq constructor.
     * @param array $config 配置信息
     */
    private function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);

        ['host' => $host, 'port' => $port, 'user' => $user, 'password' => $password, 'vhost' => $vhost,
            'is_delay' => $this->isDelay, 'pre_exchange' => $this->preExchange] = $this->config;
        $this->connection = new AMQPStreamConnection($host, $port, $user, $password, $vhost);
        $this->channel = $this->connection->channel();
    }

    /**
     * 连接
     * @param array $config
     * @return Mq
     */
    public static function conn(array $config = []): self
    {
        return new self($config);
    }

    /**
     * 发送消息
     * @param string $routingKey
     * @param string $data json字符串
     * @param array $properties
     * @return $this
     * @throws Exception
     */
    public function send(string $routingKey, string $data, array $properties = []): self
    {
        if (empty($data)) {
            throw new Exception('发送数据不能为空！');
        }
        /*if (!in_array($type, self::ALLOW_EXCHANGE_TYPE)) {        //todo:目前只支持topic
            throw new BaseException('不支持的交换机类型');
        }*/
        ['exchange_key' => $exchange, 'exchange_type' => $exchangeType, 'passive' => $passive, 'durable' => $durable,
            'auto_delete' => $autoDelete] = $this->config;

        if (empty($exchange)) {
            $exchange = explode('.', $routingKey);
            $exchange = $exchange[0] . '.' . $exchange[1];
        }

        $this->channel->exchange_declare($exchange, $exchangeType, $passive, $durable, $autoDelete);
        $msg = new AMQPMessage($data, $properties);
        $this->channel->basic_publish($msg, $exchange, $routingKey);
        return $this;
    }

    /**
     * 接收消息
     * @param array $routingList
     * @param $callback
     * @param $delayCallback
     * @return $this
     * @throws ErrorException
     */
    public function receive(array $routingList, $callback, $delayCallback = ''): self
    {
        ['exchange_type' => $exchangeType, 'exclusive' => $exclusive, 'no_ack' => $noAck, 'nowait' => $nowait,
            'passive' => $passive, 'durable' => $durable, 'auto_delete' => $autoDelete,
            'consumer_tag' => $consumerTag, 'no_local' => $noLocal] = $this->config;

        foreach ($routingList as $route) {
            $exchangeType = $route->getExchangeType() ?: $exchangeType;
            $this->channel->exchange_declare($route->getExchange(), $exchangeType, $passive, $durable, $autoDelete);
            [$qName, ,] = $this->channel->queue_declare($route->getQueue(), $passive, $durable, $exclusive, $autoDelete);
            $this->channel->queue_bind($qName, $route->getExchange(), $route->getRoute());
            $this->channel->basic_consume($qName, $consumerTag, $noLocal, $noAck, $exclusive, $nowait, $callback);
        }

        //延迟消息
        if (!empty($delayCallback) && $this->isDelay) {
            $this->receiveDelayMessage($delayCallback);
        }

        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
        return $this;
    }

    private function receiveDelayMessage($callback): void
    {
        $this->createDelay();
        $this->channel->basic_qos(null, 1, null);
        $this->channel->basic_consume($this->getDelayQueue(), '', false, false, false, false, $callback);
    }

    private function createDelay(): string
    {
        //延迟交换机和延迟列队
        $delayExchangeName = $this->getDelayExchangeName();
        $delayQueueName = $this->getDelayQueue();
        $this->channel->exchange_declare($delayExchangeName, 'direct', false, false, false);        //创建死信交换机
        $this->channel->queue_declare($this->getDelayQueue(), false, true, false, false);
        $this->channel->queue_bind($delayQueueName, $delayExchangeName, $this->getDelayRouteName());

        return $delayExchangeName;
    }

    private function createCache(DelayConfig $delayConfig): string
    {
        //临时缓存交换机和临时缓存列队
        $cacheExchangeName = $this->getCacheExchangeName();
        $cacheQueueName = $this->getCacheQueue($delayConfig->getName());
        $this->channel->exchange_declare($cacheExchangeName, 'topic', false, false, false);         //创建死信缓存数据交换机
        $tale = new AMQPTable();
        $tale->set('x-dead-letter-exchange', $this->getDelayExchangeName());
        $tale->set('x-dead-letter-routing-key', $this->getDelayRouteName());
        $tale->set('x-message-ttl', $delayConfig->getExpiry());
        $this->channel->queue_declare($cacheQueueName, false, true, false, false, false, $tale);
        $this->channel->queue_bind($cacheQueueName, $cacheExchangeName, $this->getCacheRouter($delayConfig->getName()));
        return $cacheExchangeName;
    }

    /**
     * 发送延迟消息
     * @param DelayConfig $delayConfig
     * @param array $data
     * @param string $key
     * @param $properties
     * @return Mq
     */
    public function sendDelay(DelayConfig $delayConfig, array $data, string $key, $properties): Mq
    {
        $this->createDelay();
        $cacheExchangeName = $this->createCache($delayConfig);

        $delayMsg = new DelayMessage($key, $data);

        $msg = new AMQPMessage(json_encode($delayMsg->toArray()), $properties);
        $this->channel->basic_publish($msg, $cacheExchangeName, $this->getCacheRouter($delayConfig->getName()));

        return $this;
    }

    /**
     * @return string 获取死信交换机名称
     */
    private function getDelayExchangeName(): string
    {
        return "{$this->preExchange}.delay";
    }

    /**
     * @return string 获取死信交换机名称
     */
    private function getCacheExchangeName(): string
    {
        return "{$this->preExchange}.cache";
    }

    /**
     * 获取延迟列队名称
     * @return string
     */
    private function getDelayQueue(): string
    {
        return "{$this->appName}.delay";
    }

    /**
     * 获取缓存列队名称
     * @param string $name
     * @return string
     */
    private function getCacheQueue(string $name): string
    {
        return "{$this->appName}.cache.{$name}";
    }

    /**
     * 获取延迟列队路由名
     * @return string
     */
    private function getDelayRouteName(): string
    {
        return "{$this->appName}.delay";
    }

    /**
     * 关闭连接
     * @throws Exception
     */
    public function close(): void
    {
        $this->channel->close();
        $this->connection->close();
    }

    /**
     * @return string
     */
    public function getAppName(): string
    {
        return $this->appName;
    }

    /**
     * @param string $appName
     * @return Mq
     */
    public function setAppName(string $appName): self
    {
        $this->appName = $appName;
        return $this;
    }

    /**
     * 获取缓存通道路由
     * @param string $cacheName
     * @return string
     */
    public function getCacheRouter(string $cacheName): string
    {
        return "{$this->appName}.cache.{$cacheName}";
    }
}