<?php
interface ae {
    const AE_NONE = 0;
    const AE_FILE_EVENTS = 1;
    const AE_TIME_EVENTS = 2;
    const AE_ALL_EVENTS = 3; // 1 | 2
    
    const AE_READABLE = 1;
    const AE_WRITABLE = 2;
    public function aeApiPoll(aeEventLoop $aeEventLoop, $tvp);
    public function aeApiCreate(aeEventLoop $aeEventLoop);
    public function aeApiAddEvent(aeEventLoop $aeEventLoop, $fd, $mask);
}

abstract class ae_abstract implements ae {
    
}

class aeEventLoop {
    private $ae;
    
    public $maxfd = 1024;
    public $setsize;
    public $lastTime;
    
    /**
     * @var array[aeFileEvent]
     * array(fd => instance of aeFileEvent)
     */
    public $events = [];
    
    /**
     * @var array[aeFiredEvent]
     * array(fd => instance of aeFiredEvent)
     */
    public $fired = [];
    
    public $stop;

    /**
     * @var ae_select_aeApiState
     */
    public $apidata;
    
    /**
     * @var Closure
     */
    private $beforesleep;
    
    public function __construct($setsize = 0) {
        $this->ae = new ae_select();
        $this->apidata = new ae_select_aeApiState();
        
        $this->aeCreateEventLoop($setsize);
    }
    
    public function aeCreateEventLoop($setsize) {
        // 初始化文件事件结构和已就绪文件事件结构数组
        for ($i = 0; $i < $setsize; $i++) {
            $this->events[$i] = new aeFileEvent();
            $this->fired[$i] = new aeFiredEvent();
        }
        
        $this->setsize = $setsize;
        $this->lastTime = time();
        
        $this->stop = false;
        $this->maxfd = -1;
        $this->beforesleep = null;
        
        $this->ae->aeApiCreate($this);
        
        // 初始化监听事件
        for ($i = 0; $i < $setsize; $i++) {
            $this->events[$i]->mask = ae::AE_NONE;
        }

        // 返回事件循环
        return $this;        
    }
    
    public function aeSetBeforeSleepProc(Closure $beforesleep) {
        $this->beforesleep = $beforesleep;
        return $this;
    }
    
    // 入口
    public function aeMain() {
        $this->stop = false;
        
        while (!$this->stop) {
            if ($this->beforesleep !== null) {
                $callback = $this->beforesleep;
                $callback();
            }
            
            $this->aeProcessEvents(ae::AE_ALL_EVENTS);
        }
        return $this;
    }
    
    public function aeProcessEvents($flags) {
        // 先只处理文件事件
        $numevents = $this->ae->aeApiPoll($this, $tvp = 0);
echo __METHOD__, "\t", 'aeApiPoll() returns ', $numevents, PHP_EOL;        
        for ($j = 0; $j < $numevents; $j++) {
            
            /*@var $fe aeFileEvent*/
            $fe = $this->events[$this->fired[$j]->fd];
echo __METHOD__, "\t", 'fd = ', $this->fired[$j]->fd, "\t", $fe, PHP_EOL;
// echo $this->fired[$j], PHP_EOL;

            $mask = $this->fired[$j]->mask;
            $fd = $this->fired[$j]->fd;
            $rfired = false;

           /* note the fe->mask & mask & ... code: maybe an already processed
            * event removed an element that fired and we still didn't
            * processed, so we check if the event is still valid. */
            // 读事件
            if ($fe->mask & $mask & ae::AE_READABLE) {
                // rfired 确保读/写事件只能执行其中一个
                $rfired = true;
                $rfileProc = $fe->rfileProc;
                $rfileProc($this, $fd, $fe->clientData, $mask);
            }

            // 写事件
            if ($fe->mask & $mask & ae::AE_WRITABLE) {
                if (!$rfired || $fe->wfileProc != $fe->rfileProc) {
                    $wfileProc = $fe->wfileProc;
                    $wfileProc($this, $fd, $fe->clientData, $mask);
                }
            }

            
        }
    }
    
    public function aeDeleteEventLoop() {
        return $this;
    }
    
    /*
     * 根据 mask 参数的值，监听 fd 文件的状态，
     * 当 fd 可用时，执行 proc 函数
     */
    public function aeCreateFileEvent($fd, $mask, Closure $proc, $clientData) {
        // 取出文件事件结构
        $fe = $this->events[$fd];
        
        // 监听指定 fd 的指定事件
        if ($this->ae->aeApiAddEvent($this, $fd, $mask) == -1) {
            return false;
        }

        // 设置文件事件类型，以及事件的处理器
        $fe->mask |= $mask;
        if ($mask & ae::AE_READABLE) {
echo 'Set rfileProc', PHP_EOL;            
            $fe->rfileProc = $proc;
        }
        
        if ($mask & ae::AE_WRITABLE) {
            $fe->wfileProc = $proc;
        }

        // 私有数据
        $fe->clientData = $clientData;

        // 如果有需要，更新事件处理器的最大 fd
        if ($fd > $this->maxfd) {
            $this->maxfd = $fd;
        }
echo __METHOD__, "\t", 'fd = ', $fd, "\t", $fe, PHP_EOL;
        return true;
    }
}

class aeFileEvent {
    public $mask;
    
    /**
     * @var callback
     */
    public $rfileProc;
    
    /**
     * @var callback
     */
    public $wfileProc;
    
    public $clientData;

    public function __toString() {
        return 'FileEvent (mask: '. $this->mask
                    . ')';
    }
}

class aeFiredEvent {
    public $fd;
    public $mask;

    public function __toString() {
        return 'FiredFileEvent (fd: '. $this->fd
                . ', mask: ' . $this->mask . ')';
    }
}
