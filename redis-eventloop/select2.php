<?php

class fd_set {
    private $fds = [];
    
    public function __construct($fds = array()) {
        $this->fds = $fds;
    }
    
    public function addFd($fd) {
        $this->fds[$fd];
    }       
    
    public function getChangedFdSet() {
        $fds = $this->fds;
        shuffle($fds);
        $newfds = array_slice($fds, 0, mt_rand(0, count($fds)));
        
        return new self($newfds);
    }
    
    public function count() {
        return count($this->fds);
    }
}

function FD_ISSET($fd, fd_set $fds) {
    $fds->addFd($fd);
}

function select($nfds, fd_set &$readfds = null, fd_set &$writefds = null,
                  fd_set &$exceptfds = null, $timeout = null) {
    sleep(1);
    
    $readfds = $readfds->getChangedFdSet();
    $writefds = $writefds->getChangedFdSet();
    
    return $readfds->count() + $writefds->count();
}