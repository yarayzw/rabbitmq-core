<?php


namespace hq\mq;


abstract class MqSendDataStruct
{
    abstract public function toArray(): array;
}