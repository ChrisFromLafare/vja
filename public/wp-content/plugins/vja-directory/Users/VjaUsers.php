<?php

/**
 * Classe utilitaire utilisée pour iterer une liste (tableau) d'utilisateurs
 * 
 *
 */
namespace VjaDirectory\Users;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VjaUsers implements \Iterator {
    private $users;
    private $position = 0;

    function __construct($users) {
        $this->users = $users;
    }

    public function rewind() {
        $this->position = 0;
    }

    public function current() {
        return new VjaUser($this->users[$this->position]);
    }

    public function key() {
        return $this->position;
    }

    public function next() {
        ++$this->position;
    }

    public function valid() {
        return isset($this->users[$this->position]);
    }
}