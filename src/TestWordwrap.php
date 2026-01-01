<?php

namespace Arielenter\TestWordwrap;

trait TestWordwrap
{
    public const string REGULAR_ALPHABET = 'abcdefghijklmnopqrstuvwxyz';
    public string $charactersPool = self::REGULAR_ALPHABET;
    public const string SPANISH_CHARACTERS = 'áéíóúüñÁÉÍÓÚÜÑ¿¡';
    public const string CYRILLIC_CHARACTERS = 'АБВГҐЃДЂЄЕЁЖЗЗ́ЅИІЇЙЈКЛЉМНЊОПРСС́Т'
        . 'ЋЌУЎФХЦЧЏШЩЪЫЬЭЮЯабвгґѓдђєеёжзз́ѕиіїйјклљмнњопрсс́тћќуўфхцчџшщъыьэюя';

    /**
     * Creates an example of a wrapped and unwrapped text.
     *
     * @param int    $maxLineLength  Max size a line can go until it has to be
     *                               wrapped.
     * @param int    $indentation    Optional. Number of spaces every line,
     *                               wrapped or unwrapped, must start with.
     * @param string $startLinesWith Optional. After indentation, a string that
     *                               every line will start with.
     * @param string $firstColumns   Optional. By give a representation of the
     *                               first columns of a table row, an example of
     *                               a table row with the last column wrapped
     *                               and unwrapped will be produced.
     *
     * @return array Value 0 holds the unwrapped version of the example, and
     *               value 1 the wrapped one.
     */
    public function unwrappedAndWrappedExample(
        int $maxLineLength,
        int $indentWidth = 0,
        string $startLinesWith = '',
        string $firstColumns = ''
    ): array {
        $beggingOfLine = str_repeat(' ', $indentWidth) . $startLinesWith;
        $firstPart = $beggingOfLine . $firstColumns;
        $width = $maxLineLength - mb_strlen($firstPart);
        $lines = $this->fourLinesWithRandomText($width)[0];
        $unwrapped = $firstPart . join(' ', $lines);
        $offset = str_repeat(' ', mb_strlen($firstColumns));
        $newLine = "\n" . $beggingOfLine . $offset;
        $wrapped = $firstPart . join($newLine, $lines);

        return [ $unwrapped, $wrapped ];
    }

    /**
     * Creates four lines of random text. Lines 1 and 3 will go all the way to
     * the length given, while line 2 and 4 will stop a word short. Line 3 in
     * the other hand will start with a word the size of the remainer of line 2
     * or a bit longer.
     *
     * @param int $length    Length used as the reference to create the lines.
     * @param int $remaining Optional. First line will start with a word the
     *                       size given in this parameter.
     *
     * @return array Value 0 contains an array with the 4 lines just created,
     *               and value 1 contains and integer which coresponse to the
     *               remaining number of characters line 4 was short from
     *               reaching ‘$length’.
     */
    public function fourLinesWithRandomText(int $length, int $remaining = 0)
    {
        $lines = [];
        $remaining = ($remaining == 0) ? random_int(1, 12) : $remaining ;
        for ($x = 1; $x <= 2; $x++) {
            $remaining = $remaining + random_int(0, 4);
            $lines[] = $this->randomWord($remaining) . ' '
                . $this->randomText($length - $remaining - 1);
            $remaining = random_int(3, 12);
            $lines[] = $this->randomText($length - $remaining);
        }
        return [ $lines, $remaining ];
    }

    /**
     * Creates a string with random words separeted by a single space up to a
     * given length.
     *
     * @param int $length How far (number of characters) should the text go up
     *                    to.
     *
     * @return string
     */
    public function randomText(int $length): string
    {
        $maxChunkSize = 13;
        $text = '';
        while (mb_strlen($text) <= ($length - $maxChunkSize)) {
            $randomSize = random_int(1, $maxChunkSize - 1); // One for space
            $randomWord = $this->randomWord($randomSize);
            $text = ($text == '') ? $randomWord : "$text $randomWord";
        }
        $remaining = $length - mb_strlen($text);
        if ($remaining > 0) {
            $size = ($remaining == 1) ? 1 : $remaining - 1;
            $randomWord = $this->randomWord($size);
            $lastChunk = ($remaining == 1) ? $randomWord : " $randomWord";
            $text .= $lastChunk;
        }
        return $text;
    }

    /**
     * Creates a random word from a given pool of characters.
     *
     * @param ?int    $size     How long word be be.
     * @param ?string $charPool Characters that will be used to create the word.
     *
     * @return string A random word.
     */
    public function randomWord(int $size, ?string $charPool = null): string
    {
        $characters = mb_str_split($charPool ?? $this->charactersPool);
        $randomWord = '';

        for ($i = 1; $i <= $size; $i++) {
            $index = random_int(0, count($characters) - 1);
            $randomWord .= $characters[$index];
        }

        return $randomWord;
    }
}
