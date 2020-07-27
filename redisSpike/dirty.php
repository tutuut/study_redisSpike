<?php
/**
 * 商品抢购
 */
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

//第一次，A客户端获取到销量的时候，进入到逻辑判读，条件是满足，执行到sleep
//
$num = $redis->get('num');
//销量小于库存的情况下可以卖出
if($num < 1) {
	sleep(3);//阻塞IO，消耗3秒，模拟购买
	$store = $redis->incr('num');
	print_r($store);
} else {
	echo "sold out";
}
