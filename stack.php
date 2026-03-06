<?php

class Stack {
    private $items = [];

    public function push($item) {
        $this->items[] = $item;
    }

    public function pop() {
        return array_pop($this->items);
    }

    public function isEmpty(): bool {
        return empty($this->items);
    }

    public function toArray(): array {
        return array_reverse($this->items);
    }
}

function isLeapYear(int $year): bool {
    return ($year % 4 === 0 && $year % 100 !== 0) || ($year % 400 === 0);
}

function getDaysInMonth(int $month, int $year): int {
    if ($month < 1) { 
        $month = 12;
        $year -= 1;
    }
    $days = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    if ($month == 2 && isLeapYear($year)) {
        return 29;
    }
    return $days[$month - 1];
}

function calculateTimeDifference(string $startDateTimeStr, string $endDateTimeStr): ?array {
    $startParts = explode(' ', $startDateTimeStr);
    $endParts = explode(' ', $endDateTimeStr);
    if (count($startParts) !== 2 || count($endParts) !== 2) return null;

    $startDate = explode('-', $startParts[0]);
    $startTime = explode(':', $startParts[1]);
    $endDate = explode('-', $endParts[0]);
    $endTime = explode(':', $endParts[1]);
    if (count($startDate) !== 3 || count($startTime) !== 3 || count($endDate) !== 3 || count($endTime) !== 3) return null;

    $startAll = array_map('intval', array_merge($startDate, $startTime));
    $endAll = array_map('intval', array_merge($endDate, $endTime));

    $startStack = new Stack();
    $endStack = new Stack();
    foreach ($startAll as $val) $startStack->push($val);
    foreach ($endAll as $val) $endStack->push($val);

    $diffStack = new Stack();
    $borrow = 0;

    // Seconds
    $startVal = $startStack->pop();
    $endVal = $endStack->pop() - $borrow;
    if ($endVal < $startVal) {
        $endVal += 60;
        $borrow = 1;
    } else {
        $borrow = 0;
    }
    $diffStack->push($endVal - $startVal);

    // Minutes
    $startVal = $startStack->pop();
    $endVal = $endStack->pop() - $borrow;
    if ($endVal < $startVal) {
        $endVal += 60;
        $borrow = 1;
    } else {
        $borrow = 0;
    }
    $diffStack->push($endVal - $startVal);

    // Hours
    $startVal = $startStack->pop();
    $endVal = $endStack->pop() - $borrow;
    if ($endVal < $startVal) {
        $endVal += 24;
        $borrow = 1;
    } else {
        $borrow = 0;
    }
    $diffStack->push($endVal - $startVal);

    // Days
    $startVal = $startStack->pop();
    $endVal = $endStack->pop() - $borrow;
    if ($endVal < $startVal) {
        $endVal += getDaysInMonth($endAll[1] - 1, $endAll[0]);
        $borrow = 1;
    } else {
        $borrow = 0;
    }
    $diffStack->push($endVal - $startVal);

    // Months
    $startVal = $startStack->pop();
    $endVal = $endStack->pop() - $borrow;
    if ($endVal < $startVal) {
        $endVal += 12;
        $borrow = 1;
    } else {
        $borrow = 0;
    }
    $diffStack->push($endVal - $startVal);

    // Years
    $startVal = $startStack->pop();
    $endVal = $endStack->pop() - $borrow;
    $diffStack->push($endVal - $startVal);

    return $diffStack->toArray();
}

function displayTimeDifference(string $startDateTimeStr, string $endDateTimeStr): string {
    if (isCapsuleUnlocked($endDateTimeStr)) {
        return "Unlocked";
    }

    $diff = calculateTimeDifference($startDateTimeStr, $endDateTimeStr);
    if ($diff === null) {
        return "Invalid date format";
    }
    
    return sprintf('%dy %dm %dd %dh %dm %ds', $diff[5], $diff[4], $diff[3], $diff[2], $diff[1], $diff[0]);
}

function isCapsuleUnlocked(string $unlockDateTimeStr): bool {
    $now = date('Y-m-d H:i:s');
    return $now >= $unlockDateTimeStr;
}
