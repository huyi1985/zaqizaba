<?php
interface ae {
    const AE_FILE_EVENTS = 1;
    const AE_TIME_EVENTS = 2;
    const AE_ALL_EVENTS = 3; // 1 | 2
    
    public function aeApiPoll(aeEventLoop $aeEventLoop, $tvp);
}

abstract class ae_abstract implements ae {
    
}

class aeEventLoop {
    private $ae;
    
    public $maxfd = 1024;
    public $setsize;
    
    /**
     * @var array[aeFileEvent]
     * array(fd => instance of aeFileEvent)
     */
    public $events = [];
    public $fired = [];
    
    public $stop;
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
        $this->events = array();
        $this->fired = array();
        
        $this->setsize = $setsize;
        $this->stop = false;
        
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
        for ($j = 0; $j < $numevents; $j++) {
            
            /*@var $fe aeFileEvent*/
            $fe = $this->events[$this->fired[$j]->fd];
            
            //@todo Who does populate the events array
            
            $wfileProc = $fe->wfileProc;
            $wfileProc();
        }
    }
    
    public function aeDeleteEventLoop() {
        return $this;
    }
    
    public function aeCreateFileEvent($fd, $mask, $proc, $clientData) {
        
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
}

class aeFiredEvent {
    public $fd;
    public $mask;
}
