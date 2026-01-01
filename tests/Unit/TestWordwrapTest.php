<?php

namespace Tests\Unit;

use Arielenter\TestWordwrap\TestWordwrap;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TestWordwrapTest extends TestCase
{
    use TestWordwrap;

    public function setUp(): void
    {
        $this->charactersPool = self::REGULAR_ALPHABET;
        parent::setUp();
    }

    #[Test]
    public function a_random_word_can_be_created_from_given_pool(): void
    {
        $pool = 'aeiou';
        $size = 6;
        $word = $this->randomWord($size, $pool);
        $this->assertEquals(0, preg_match('/[b-df-hj-np-tv-z]/', $word));
        $this->assertEquals(1, preg_match("/^[$pool]{{$size}}$/", $word));

        $word = $this->randomWord($size);
        $this->assertEquals(1, preg_match("/^[a-z]{{$size}}$/", $word));
    }

    #[Test]
    public function random_word_creator_is_multi_byte_safe(): void
    {
        $size = 10;
        $pool = self::SPANISH_CHARACTERS;
        $word = $this->randomWord($size, $pool);
        $this->assertEquals(1, preg_match("/^[$pool]{{$size}}$/u", $word));

        $pool = self::CYRILLIC_CHARACTERS;
        $this->charactersPool = $pool;
        $word = $this->randomWord($size);
        $this->assertEquals(1, preg_match("/^[$pool]{{$size}}$/u", $word));
    }

    #[Test]
    public function can_create_a_string_with_multiple_words(): void
    {
        $length = 80;
        $randomText = $this->randomText($length);
        $pattern = "/^{$this->multipleWordsPattern()}/";
        $this->assertEquals(1, preg_match($pattern, $randomText));
        $this->assertEquals($length, mb_strlen($randomText));
    }

    public function multipleWordsPattern(): string
    {
        return "[{$this->charactersPool}]+( [{$this->charactersPool}]+)+$";
    }

    #[Test]
    public function random_text_is_multi_byte_safe(): void
    {
        $pools = [ self::SPANISH_CHARACTERS, self::CYRILLIC_CHARACTERS ];
        foreach ($pools as $pool) {
            $this->charactersPool = $pool;
            $this->can_create_a_string_with_multiple_words();
        }
    }

    #[Test]
    public function creates_four_lines_of_random_text_of_different_sizes(): void
    {
        $length = 80;
        $lines = $this->fourLinesWithRandomText($length)[0];
        $this->assertEquals($length, mb_strlen($lines[0]));
        $this->assertLessThan($length, mb_strlen($lines[1]));
        $this->assertEquals($length, mb_strlen($lines[2]));
        $this->assertLessThan($length, mb_strlen($lines[3]));
    }

    #[Test]
    public function returns_fourth_line_remaining_to_length(): void
    {
        $length = 80;
        [ $lines, $remaining ] = $this->fourLinesWithRandomText($length);
        $expected = $length - $remaining;
        $this->assertEquals($expected, mb_strlen($lines[3]));
    }

    #[Test]
    public function second_arg_stablishes_first_word_min_size(): void
    {
        $remaining = 4;
        $lines = $this->fourLinesWithRandomText(80, $remaining)[0];
        $actual = $this->sizeOfFirstWordOfLine($lines[0]);
        $this->assertGreaterThanOrEqual($remaining, $actual);
        $this->assertLessThanOrEqual($remaining + 4, $actual);
    }

    public function sizeOfFirstWordOfLine(string $line): string
    {
        $pool = $this->charactersPool;
        preg_match("/^[$pool]+/", $line, $matches);

        return mb_strlen($matches[0]);
    }

    #[Test]
    public function third_starts_with_a_word_the_size_of_second_reamin(): void
    {
        $length = 80;
        $lines = $this->fourLinesWithRandomText($length)[0];
        $secondLineRemain = $length - mb_strlen($lines[1]);
        $actual = $this->sizeOfFirstWordOfLine($lines[2]);
        $this->assertGreaterThanOrEqual($secondLineRemain, $actual);
        $this->assertLessThanOrEqual($secondLineRemain + 4, $actual);
    }

    #[Test]
    public function four_lines_creator_is_multi_byte_safe(): void
    {
        $pools = [ self::SPANISH_CHARACTERS, self::CYRILLIC_CHARACTERS ];
        foreach ($pools as $pool) {
            $this->charactersPool = $pool;
            $this->creates_four_lines_of_random_text_of_different_sizes();
            $this->returns_fourth_line_remaining_to_length();
            $this->second_arg_stablishes_first_word_min_size();
            $this->third_starts_with_a_word_the_size_of_second_reamin();
        }
    }

    #[Test]
    public function creates_unwrapped_and_wrapped_text_examples(): void
    {
        $maxLen = $max = 80;
        $textPattern = $this->multipleWordsPattern();
        [ $unwrapped, $wrapped ] = $this->unwrappedAndWrappedExample($maxLen);
        $this->checkSizes($maxLen, $unwrapped, $wrapped);
        $this->assertEquals($unwrapped, str_replace("\n", ' ', $wrapped));
        $this->assertEquals(1, preg_match("/^$textPattern/", $unwrapped));

        $indentWidth = $iw = 4;
        $indent = str_repeat(' ', $indentWidth);
        [ $uw, $w ] = $this->unwrappedAndWrappedExample($max, $indentWidth);
        $this->checkSizes($max, $uw, $w);
        $this->assertEquals($uw, str_replace("\n$indent", ' ', $w));
        $this->assertEquals(1, preg_match("/^$indent$textPattern/", $uw));

        $start = '// ';
        [ $uw, $w ] = $this->unwrappedAndWrappedExample($max, $iw, $start);
        $beginning = $indent . $start;
        $this->checkSizes($max, $uw, $w);
        $this->assertEquals($uw, str_replace("\n$beginning", ' ', $w));
        $this->assertEquals(1, preg_match("~^$beginning$textPattern~", $uw));

        $str = ' * ';
        $cols = '@param string $var ';
        [ $uw, $w ] = $this->unwrappedAndWrappedExample($max, $iw, $str, $cols);
        $newLine = "\n" . $indent . $str . str_repeat(' ', mb_strlen($cols));
        $beginning = preg_quote($indent . $str . $cols);
        $this->checkSizes($max, $uw, $w);
        $this->assertEquals($uw, str_replace($newLine, ' ', $w));
        $this->assertEquals(1, preg_match("/^$beginning$textPattern/", $uw));
    }

    public function checkSizes(
        int $maxLineLength,
        string $unwrapped,
        string $wrapped
    ): void {
        $this->assertGreaterThan($maxLineLength, mb_strlen($unwrapped));
        foreach (explode("\n", $wrapped) as $line) {
            $this->assertLessThanOrEqual($maxLineLength, mb_strlen($line));
        }
    }

    #[Test]
    public function unwrapped_and_wrapped_examples_is_multi_byte_safe(): void
    {
        $pools = [ self::SPANISH_CHARACTERS, self::CYRILLIC_CHARACTERS ];
        foreach ($pools as $pool) {
            $this->charactersPool = $pool;
            $this->creates_unwrapped_and_wrapped_text_examples();
        }
    }
}
