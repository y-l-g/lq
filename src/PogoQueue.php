<?php

namespace Pogo\Queue;

use Illuminate\Queue\Queue;
use Illuminate\Contracts\Queue\Queue as QueueContract;

class PogoQueue extends Queue implements QueueContract
{
    public function size($queue = null)
    {
        return 0;
    }

    public function push($job, $data = '', $queue = null)
    {
        return $this->pushRaw($this->createPayload($job, $queue, $data), $queue);
    }

    public function pushRaw($payload, $queue = null, array $options = [])
    {
        if (function_exists('pogo_queue')) {
            \pogo_queue($payload);
        } else {
            throw new \RuntimeException("Pogo Queue extension is not enabled.");
        }
    }

    public function later($delay, $job, $data = '', $queue = null)
    {
        return $this->push($job, $data, $queue);
    }

    public function pop($queue = null)
    {
        return null;
    }
}