<?php

require 'ae_select.php';

define('REDIS_MIN_RESERVED_FDS', 32);
define('REDIS_EVENTLOOP_FDSET_INCR', REDIS_MIN_RESERVED_FDS + 96);

$maxclients = 1024 - REDIS_MIN_RESERVED_FDS;
$setsize = $maxclients + REDIS_EVENTLOOP_FDSET_INCR;
$el = new aeEventLoop($setsize);

// initServer()

// @see networking.c
// 创建一个 TCP 连接处理器
$acceptTcpHandler = function(aeEventLoop $el, $fd, $privdata, $mask) {
    acceptCommonHandler($fd, $mask);
};

function acceptCommonHandler($fd, $flags) {
    echo 'acceptCommonHandler', PHP_EOL;
}

$fd = 4;
$el->aeCreateFileEvent($fd, ae::AE_READABLE, $acceptTcpHandler, NULL);

$el->aeSetBeforeSleepProc(function() {
    echo 'aeSetBeforeSleepProc', PHP_EOL;
});

$el->aeMain();
$el->aeDeleteEventLoop();