<?php

namespace Mix\SyncInvoke;

use Mix\Pool\ConnectionTrait;
use Mix\SyncInvoke\Exception\CallException;
use Mix\SyncInvoke\Exception\InvokeException;
use Swoole\Coroutine\Client;

/**
 * Class Connection
 * @package Mix\SyncInvoke
 */
class Connection
{

    use ConnectionTrait;

    /**
     * @var int
     */
    public $port = 0;

    /**
     * @var float
     */
    public $timeout = 0.0;

    /**
     * @var Client
     */
    protected $client;

    /**
     * EOF
     */
    const EOF = "-Y3ac0v\n";

    /**
     * Connection constructor.
     * @param int $port
     * @param float $timeout
     * @throws \Swoole\Exception
     */
    public function __construct(int $port, float $timeout = 5.0)
    {
        $this->port    = $port;
        $this->timeout = $timeout;
        $client        = new Client(SWOOLE_SOCK_TCP);
        $client->set([
            'open_eof_check' => true,
            'package_eof'    => static::EOF,
        ]);
        if (!$client->connect('127.0.0.1', $port, $timeout)) {
            throw new \Swoole\Exception(sprintf("Sync invoke: %s (port: %s)", $client->errMsg, $port), $client->errCode);
        }
        $this->client = $client;
    }

    /**
     * 析构
     */
    public function __destruct()
    {
        // 丢弃连接
        $this->discard();
    }

    /**
     * 关闭连接
     * @throws \Swoole\Exception
     */
    public function close()
    {
        if (!$this->client->close()) {
            $errMsg  = $this->client->errMsg;
            $errCode = $this->client->errCode;
            if ($errMsg == '' && $errCode == 0) {
                return;
            }
            throw new \Swoole\Exception($errMsg, $errCode);
        }
    }

    /**
     * Recv
     * @return mixed
     * @throws \Swoole\Exception
     */
    public function recv()
    {
        $data = $this->client->recv(-1);
        if ($data === false || $data === "") {
            throw new \Swoole\Exception($this->client->errMsg, $this->client->errCode);
        }
        return $data;
    }

    /**
     * Send
     * @param string $data
     * @throws \Swoole\Exception
     */
    public function send(string $data)
    {
        $len  = strlen($data);
        $size = $this->client->send($data);
        if ($size === false) {
            throw new \Swoole\Exception($this->client->errMsg, $this->client->errCode);
        }
        if ($len !== $size) {
            throw new \Swoole\Exception('The sending data is incomplete, it may be that the socket has been closed by the peer.');
        }
    }

}
