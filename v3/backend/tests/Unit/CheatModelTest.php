<?php

namespace Tests\Unit;

use App\Models\Cheat;
use PHPUnit\Framework\TestCase;

/**
 * 純單元測試：不碰 DB，只驗 normalizer + 常數。
 *   ./vendor/bin/phpunit tests/Unit/CheatModelTest.php
 */
class CheatModelTest extends TestCase
{
    public function test_normalize_arrows_to_uldr(): void
    {
        $this->assertSame('UUDDLRLRBA', Cheat::normalizeCode('↑↑↓↓←→←→BA'));
        $this->assertSame('UUDDLRLRBA', Cheat::normalizeCode('上上下下左右左右BA'));
        $this->assertSame('UUDDLRLRBA', Cheat::normalizeCode('⬆⬆⬇⬇⬅➡⬅➡BA'));
    }

    public function test_normalize_strips_whitespace_and_separators(): void
    {
        $this->assertSame('UDLRBA', Cheat::normalizeCode('U D L R B A'));
        $this->assertSame('UDLRBA', Cheat::normalizeCode("U,D,L,R,B,A"));
        $this->assertSame('UDLRBA', Cheat::normalizeCode("U、D、L、R、B、A"));
        $this->assertSame('UDLRBA', Cheat::normalizeCode("u+d+l+r+b+a"));
    }

    public function test_normalize_keeps_hex_codes(): void
    {
        $this->assertSame('SXIOPO', Cheat::normalizeCode('SXIOPO'));
        $this->assertSame('7E0019:09', Cheat::normalizeCode('7E0019:09'));
    }

    public function test_set_code_attribute_populates_normalized(): void
    {
        $cheat = new Cheat();
        $cheat->code = '↑↑↓↓←→←→BA';
        $this->assertSame('↑↑↓↓←→←→BA', $cheat->code);
        $this->assertSame('UUDDLRLRBA', $cheat->code_normalized);
    }

    public function test_types_constant_has_nine_keys(): void
    {
        $this->assertCount(9, Cheat::TYPES);
        $this->assertArrayHasKey('button_sequence', Cheat::TYPES);
        $this->assertArrayHasKey('password', Cheat::TYPES);
        $this->assertArrayHasKey('game_genie', Cheat::TYPES);
        $this->assertArrayHasKey('glitch', Cheat::TYPES);
        $this->assertArrayHasKey('easter_egg', Cheat::TYPES);
    }

    public function test_difficulties_constant(): void
    {
        $this->assertCount(3, Cheat::DIFFICULTIES);
        $this->assertSame(['easy', 'medium', 'hard'], array_keys(Cheat::DIFFICULTIES));
    }
}
