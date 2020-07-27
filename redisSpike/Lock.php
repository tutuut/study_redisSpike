<?php

/**
 * 分布式锁的基本条件
 * 1.互斥性
 * 2.不会发生死锁
 * 3.解铃还须系铃人
 */
class Lock
{
    protected $redis;
    protected $lockId;//记录客户端加锁ID

    public function __construct($redis)
    {
        $this->redis = $redis;
    }

    public function addLock($scene = 'seckill', $expire = 5, $retry = 5, $sleep = 100000)
    {
        //同一时刻只能有一个用户持有锁，并且不能出现死锁
        $res = false;
        while($retry-- > 0) {
            $value = session_create_id();//生成不重复的字符串
            $res = $this->redis->set($scene, $value, ['NX', 'EX' => $expire]);
            if($res) {
                $this->lockId[$scene] = $value;
                //加锁成功
                break;
            }
            echo "1尝试获取锁".PHP_EOL;
            usleep($sleep);
        }

        return $res;
    }

    public function unlock($scene)
    {
        //能够删除自己的锁，而不应该删除别人的锁
        if (isset($this->lockId[$scene])) {
            $id = $this->lockId[$scene];//当前请求记录的value值

            //在极端情况下，还有可能出现误删锁
            //Redis嵌入Lua脚本
            //1.减少网络开销：不使用Lua脚本

            $value = $this->redis->get($scene);//先取出当前数据库中记录的锁
            //从redis当中获取的id跟当前请求记录的id，是否是同一个
            if($value == $id) {
                //sleep(5);//客户端A发生了阻塞（原子性）
                return $this->redis->del($scene);
            }
        }
        return false;
    }
}

$redis = new Redis();
$redis->connect('127.0.0.1', 6379);
$lock = new Lock($redis);
$scene = 'seckill';
$expire = 10;
//如果加锁成功，某个业务只允许一个用户操作
if($lock->addLock($scene, $expire)) {
    var_dump('2执行业务逻辑');
    sleep(5);//模拟业务逻辑,      如果执行时间大于key的过期时间？？？
    $lock->unlock($scene);//解锁操作
    return;
}

var_dump('3获取锁失败');