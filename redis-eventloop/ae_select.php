<?php
require 'ae.php';
require 'select2.php';

class ae_select extends ae_abstract {
    
    public function aeApiPoll(aeEventLoop $eventLoop, $tvp) {       
        /*@var $state ae_select_aeApiState*/
        $state = $eventLoop->apidata;
        $numevents = 0;
        
        $state->_rfds = $state->rfds;
        $state->_wfds = $state->wfds;
        
        $retval = select($eventLoop->maxfd + 1, $state->_rfds, $state->_wfds);
echo 'select() returns ', $retval, PHP_EOL;         
        if ($retval > 0) {
            for ($j = 0; $j <= $eventLoop->maxfd; $j++) {
                $mask = 0;
                
                $fe = $eventLoop->events[$j];
                if (FD_ISSET($j, $state->_rfds)) {
                    $mask |= ae::AE_READABLE;
                }
                
                if (FD_ISSET($j, $state->_wfds)) {
                    $mask |= ae::AE_WRITABLE;
                }
                
                $ffe = new aeFiredEvent();
                $ffe->fd = $j;
                $ffe->mask = $mask;
                
                $eventLoop->fired[$numevents] = $ffe;
                $numevents++;
            }
        }
    }
    
    public function aeApiCreate(aeEventLoop $eventLoop) {
        $state = new ae_select_aeApiState();
        
        FD_ZERO($state->rfds);
        FD_ZERO($state->wfds);
        
        $eventLoop->apidata = $state;        
    }
    
    public function aeApiAddEvent(aeEventLoop $aeEventLoop, $fd, $mask) {
        /*@var $state ae_select_aeApiState*/
        $state = $aeEventLoop->apidata;
        
        if ($mask & ae::AE_READABLE) {
            FD_SET($fd, $state->rfds);
        }
        
        if ($mask & ae::AE_WRITABLE) {
            FD_SET($fd, $state->wfds);
        }
        
        return 0;
    }
}

class ae_select_aeApiState {
    public $rfds;
    public $wfds;
    
    /* We need to have a copy of the fd sets as it's not safe to reuse
     * FD sets after select(). */    
    public $_rfds;
    public $_wfds;
    
    public function __construct() {
        $this->rfds = new fd_set();
        $this->wfds = new fd_set();
    }
}