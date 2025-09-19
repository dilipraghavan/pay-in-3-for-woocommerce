<?php

use PHPUnit\Framework\TestCase;

final class SmokeTest extends TestCase {
    public function test_true_is_true(): void {
        $this->assertTrue(true);
    }
}