<?php
// priority queue for managing memory unlock times

class PriorityQueue {
    private $queue = [];

    // Insert an item with a given priority (timestamp or string date)
    public function insert($item, $priority) {
        $this->queue[] = ['item' => $item, 'priority' => $priority];
    }

    // Remove and return the item with the lowest priority (earliest unlock time)
    public function pop() {
        if (empty($this->queue)) return null;
        $minIdx = 0;
        foreach ($this->queue as $i => $entry) {
            if (strtotime($entry['priority']) < strtotime($this->queue[$minIdx]['priority'])) {
                $minIdx = $i;
            }
        }
        $item = $this->queue[$minIdx]['item'];
        array_splice($this->queue, $minIdx, 1);
        return $item;
    }

    // Peek at the item with the lowest priority without removing it
    public function peek() {
        if (empty($this->queue)) return null;
        $minIdx = 0;
        foreach ($this->queue as $i => $entry) {
            if (strtotime($entry['priority']) < strtotime($this->queue[$minIdx]['priority'])) {
                $minIdx = $i;
            }
        }
        return $this->queue[$minIdx]['item'];
    }

    // Return all items sorted by priority (earliest first)
    public function toSortedArray() {
        $sorted = $this->queue;
        usort($sorted, function($a, $b) {
            return strtotime($a['priority']) <=> strtotime($b['priority']);
        });
        return array_map(function($entry) { return $entry['item']; }, $sorted);
    }

    public function isEmpty() {
        return empty($this->queue);
    }

    public function size() {
        return count($this->queue);
    }
} 