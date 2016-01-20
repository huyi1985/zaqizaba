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
                
                $eventLoop->fired[$numevents]->fd = $j;
                $eventLoop->fired[$numevents]->mask = $mask;
                $numevents++;
            }
        }
    }
}

class ae_select_aeApiState {
    public $rfds;
    public $wfds;
    
    public $_rfds;
    public $_wfds;
    
    public function __construct() {
        $this->rfds = new fd_set();
        $this->wfds = new fd_set();
    }
}