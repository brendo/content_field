<?php

	final class MessageStack implements Iterator {
	    private $messages = array();

	    public function __construct(array $messages = null) {
			$this->messages = array();

	        if (!is_null($messages)) {
	            $this->messages = $messages;
	        }
	    }

	    public function rewind() {
	        reset($this->messages);
	    }

	    public function current() {
	        return current($this->messages);
	    }

	    public function key() {
	        return key($this->messages);
	    }

	    public function next() {
	        return next($this->messages);
	    }

	    public function valid() {
	        return $this->current() !== false;
	    }

		public function length() {
			return count($this->messages);
		}

		public function append($identifier, $message) {
			if ($identifier == null) {
				$identifier = count($this->messages);
			}

			$this->messages[$identifier] = $message;

			return $identifier;
		}

		public function remove($identifier) {
			if (isset($this->messages[$identifier])) {
				unset($this->messages[$identifier]);
			}
		}

		public function flush() {
			$this->messages = array();
		}

		public function __get($identifier) {
			return isset($this->messages[$identifier])
				? $this->messages[$identifier]
				: null;
		}

		public function __isset($identifier){
			return isset($this->messages[$identifier]);
		}
	}
