<?php
// memory linkedlist

class MemoryNode {
    public $data;
    public $prev = null;
    public $next = null;

    public function __construct($data) {
        $this->data = $data;
    }
}

class MemoryList {
    private $head = null;
    private $tail = null;
    private $count = 0;

    public function add($data) {
        $node = new MemoryNode($data);
        if (!$this->head) {
            $this->head = $this->tail = $node;
        } else {
            $this->tail->next = $node;
            $node->prev = $this->tail;
            $this->tail = $node;
        }
        $this->count++;
    }

    public function count() {
        return $this->count;
    }

    public function toArray() {
        $arr = [];
        $current = $this->head;
        while ($current) {
            $arr[] = $current->data;
            $current = $current->next;
        }
        return $arr;
    }

    public function fromArray($arr) {
        $this->head = $this->tail = null;
        $this->count = 0;
        foreach ($arr as $data) {
            $this->add($data);
        }
    }

    public function filterByFriendEmail($email) {
        $filtered = [];
        $current = $this->head;
        while ($current) {
            $friends = is_array($current->data['friends']) ? $current->data['friends'] : explode(',', $current->data['friends']);
            if (in_array($email, $friends)) {
                $filtered[] = $current->data;
            }
            $current = $current->next;
        }
        return $filtered;
    }

    // Optional: Traverse forward
    public function traverseForward($callback) {
        $current = $this->head;
        while ($current) {
            $callback($current->data);
            $current = $current->next;
        }
    }

    // Optional: Traverse backward
    public function traverseBackward($callback) {
        $current = $this->tail;
        while ($current) {
            $callback($current->data);
            $current = $current->prev;
        }
    }

   
    public function find($memoryId) {
        $current = $this->head;
        while ($current) {
            if (($current->data['memory_id'] ?? $current->data['id'] ?? null) === $memoryId) {
                return $current;
            }
            $current = $current->next;
        }
        return null;
    }
    
    public function getHead() {
        return $this->head;
    }


    public function delete($memoryId) {
        $nodeToDelete = $this->find($memoryId);
        if (!$nodeToDelete) {
            return false; // Node not found
        }

        if ($nodeToDelete->prev) {
         
            $nodeToDelete->prev->next = $nodeToDelete->next;
        } else {
            // If it is the head, update the head pointer
            $this->head = $nodeToDelete->next;
        }

        if ($nodeToDelete->next) {
            // If it's not the tail, link next node back to the previous node
            $nodeToDelete->next->prev = $nodeToDelete->prev;
        } else {
            // If it is the tail, update the tail pointer
            $this->tail = $nodeToDelete->prev;
        }

        $this->count--;
        return true;
    }
}
