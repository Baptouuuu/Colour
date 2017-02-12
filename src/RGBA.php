<?php
declare(strict_types = 1);

namespace Innmind\Colour;

use Innmind\Colour\Exception\InvalidArgumentException;
use Innmind\Immutable\Str;

final class RGBA implements ConvertibleInterface
{
    const HEXADECIMAL_PATTERN_WITH_ALPHA = '~^#?(?<red>[0-9a-fA-F]{1,2})(?<green>[0-9a-fA-F]{1,2})(?<blue>[0-9a-fA-F]{1,2})(?<alpha>[0-9a-fA-F]{1,2})$~';
    const HEXADECIMAL_PATTERN_WITHOUT_ALPHA = '~^#?(?<red>[0-9a-fA-F]{1,2})(?<green>[0-9a-fA-F]{1,2})(?<blue>[0-9a-fA-F]{1,2})$~';
    const RGB_FUNCTION_PATTERN = '~^rgb\((?<red>\d{1,3}), ?(?<green>\d{1,3}), ?(?<blue>\d{1,3})\)$~';
    const PERCENTED_RGB_FUNCTION_PATTERN = '~^rgb\((?<red>\d{1,3})%, ?(?<green>\d{1,3})%, ?(?<blue>\d{1,3})%\)$~';
    const RGBA_FUNCTION_PATTERN = '~^rgba\((?<red>\d{1,3}), ?(?<green>\d{1,3}), ?(?<blue>\d{1,3}), ?(?<alpha>[01]|0?\.\d+|1\.0)\)$~';
    const PERCENTED_RGBA_FUNCTION_PATTERN = '~^rgba\((?<red>\d{1,3})%, ?(?<green>\d{1,3})%, ?(?<blue>\d{1,3})%, ?(?<alpha>[01]|0?\.\d+|1\.0)\)$~';

    private $red;
    private $blue;
    private $green;
    private $alpha;
    private $string;
    private $hsla;
    private $cmyka;

    public function __construct(
        Red $red,
        Green $green,
        Blue $blue,
        Alpha $alpha = null
    ) {
        $this->red = $red;
        $this->blue = $blue;
        $this->green = $green;
        $this->alpha = $alpha ?? new Alpha(1);

        if ($this->alpha->atMaximum()) {
            $this->string = '#'.$this->toHexadecimal();
        } else {
            $this->string = sprintf(
                'rgba(%s, %s, %s, %s)',
                $this->red->toInt(),
                $this->green->toInt(),
                $this->blue->toInt(),
                $this->alpha->toFloat()
            );
        }
    }

    public static function fromString(string $colour): self
    {
        try {
            return self::fromHexadecimal($colour);
        } catch (InvalidArgumentException $e) {
            //attempt next format
        }

        try {
            return self::fromRGBFunction($colour);
        } catch (InvalidArgumentException $e) {
            //attempt next format
        }

        return self::fromRGBAFunction($colour);
    }

    public static function fromHexadecimal(string $colour): self
    {
        $colour = (new Str($colour))->trim();

        try {
            return self::fromHexadecimalWithAlpha($colour);
        } catch (InvalidArgumentException $e) {
            return self::fromHexadecimalWithoutAlpha($colour);
        }
    }

    public static function fromHexadecimalWithAlpha(Str $colour): self
    {
        if (!$colour->matches(self::HEXADECIMAL_PATTERN_WITH_ALPHA)) {
            throw new InvalidArgumentException;
        }

        if ($colour->matches('/^#/')) {
            $colour = $colour->substring(1);
        }

        if (
            $colour->length() !== 4 &&
            $colour->length() !== 8
        ) {
            throw new InvalidArgumentException;
        }

        $matches = $colour->getMatches(self::HEXADECIMAL_PATTERN_WITH_ALPHA);

        return new self(
            Red::fromHexadecimal((string) $matches->get('red')),
            Green::fromHexadecimal((string) $matches->get('green')),
            Blue::fromHexadecimal((string) $matches->get('blue')),
            Alpha::fromHexadecimal((string) $matches->get('alpha'))
        );
    }

    public static function fromHexadecimalWithoutAlpha(Str $colour): self
    {
        if (!$colour->matches(self::HEXADECIMAL_PATTERN_WITHOUT_ALPHA)) {
            throw new InvalidArgumentException;
        }

        if ($colour->matches('/^#/')) {
            $colour = $colour->substring(1);
        }

        if (
            $colour->length() !== 3 &&
            $colour->length() !== 6
        ) {
            throw new InvalidArgumentException;
        }

        $matches = $colour->getMatches(self::HEXADECIMAL_PATTERN_WITHOUT_ALPHA);

        return new self(
            Red::fromHexadecimal((string) $matches->get('red')),
            Green::fromHexadecimal((string) $matches->get('green')),
            Blue::fromHexadecimal((string) $matches->get('blue'))
        );
    }

    public static function fromRGBFunction(string $colour): self
    {
        $colour = (new Str($colour))->trim();

        try {
            return self::fromRGBFunctionWithPoints($colour);
        } catch (InvalidArgumentException $e) {
            return self::fromRGBFunctionWithPercents($colour);
        }
    }

    public static function fromRGBFunctionWithPoints(Str $colour): self
    {
        if (!$colour->matches(self::RGB_FUNCTION_PATTERN)) {
            throw new InvalidArgumentException;
        }

        $matches = $colour->getMatches(self::RGB_FUNCTION_PATTERN);

        return new self(
            new Red((int) (string) $matches->get('red')),
            new Green((int) (string) $matches->get('green')),
            new Blue((int) (string) $matches->get('blue'))
        );
    }

    public static function fromRGBFunctionWithPercents(Str $colour): self
    {
        if (!$colour->matches(self::PERCENTED_RGB_FUNCTION_PATTERN)) {
            throw new InvalidArgumentException;
        }

        $matches = $colour->getMatches(self::PERCENTED_RGB_FUNCTION_PATTERN);

        return new self(
            Red::fromIntensity(new Intensity((int) (string) $matches->get('red'))),
            Green::fromIntensity(new Intensity((int) (string) $matches->get('green'))),
            Blue::fromIntensity(new Intensity((int) (string) $matches->get('blue')))
        );
    }

    public static function fromRGBAFunction(string $colour): self
    {
        $colour = (new Str($colour))->trim();

        try {
            return self::fromRGBAFunctionWithPoints($colour);
        } catch (InvalidArgumentException $e) {
            return self::fromRGBAFunctionWithPercents($colour);
        }
    }

    public static function fromRGBAFunctionWithPoints(Str $colour): self
    {
        if (!$colour->matches(self::RGBA_FUNCTION_PATTERN)) {
            throw new InvalidArgumentException;
        }

        $matches = $colour->getMatches(self::RGBA_FUNCTION_PATTERN);

        return new self(
            new Red((int) (string) $matches->get('red')),
            new Green((int) (string) $matches->get('green')),
            new Blue((int) (string) $matches->get('blue')),
            new Alpha((float) (string) $matches->get('alpha'))
        );
    }

    public static function fromRGBAFunctionWithPercents(Str $colour): self
    {
        if (!$colour->matches(self::PERCENTED_RGBA_FUNCTION_PATTERN)) {
            throw new InvalidArgumentException;
        }

        $matches = $colour->getMatches(self::PERCENTED_RGBA_FUNCTION_PATTERN);

        return new self(
            Red::fromIntensity(new Intensity((int) (string) $matches->get('red'))),
            Green::fromIntensity(new Intensity((int) (string) $matches->get('green'))),
            Blue::fromIntensity(new Intensity((int) (string) $matches->get('blue'))),
            new Alpha((float) (string) $matches->get('alpha'))
        );
    }

    public function red(): Red
    {
        return $this->red;
    }

    public function blue(): Blue
    {
        return $this->blue;
    }

    public function green(): Green
    {
        return $this->green;
    }

    public function alpha(): Alpha
    {
        return $this->alpha;
    }

    public function addRed(Red $red): self
    {
        return new self(
            $this->red->add($red),
            $this->green,
            $this->blue,
            $this->alpha
        );
    }

    public function subtractRed(Red $red): self
    {
        return new self(
            $this->red->subtract($red),
            $this->green,
            $this->blue,
            $this->alpha
        );
    }

    public function addBlue(Blue $blue): self
    {
        return new self(
            $this->red,
            $this->green,
            $this->blue->add($blue),
            $this->alpha
        );
    }

    public function subtractBlue(Blue $blue): self
    {
        return new self(
            $this->red,
            $this->green,
            $this->blue->subtract($blue),
            $this->alpha
        );
    }

    public function addGreen(Green $green): self
    {
        return new self(
            $this->red,
            $this->green->add($green),
            $this->blue,
            $this->alpha
        );
    }

    public function subtractGreen(Green $green): self
    {
        return new self(
            $this->red,
            $this->green->subtract($green),
            $this->blue,
            $this->alpha
        );
    }

    public function addAlpha(Alpha $alpha): self
    {
        return new self(
            $this->red,
            $this->green,
            $this->blue,
            $this->alpha->add($alpha)
        );
    }

    public function subtractAlpha(Alpha $alpha): self
    {
        return new self(
            $this->red,
            $this->green,
            $this->blue,
            $this->alpha->subtract($alpha)
        );
    }

    public function equals(self $rgba): bool
    {
        return $this->red->equals($rgba->red()) &&
            $this->green->equals($rgba->green()) &&
            $this->blue->equals($rgba->blue()) &&
            $this->alpha->equals($rgba->alpha());
    }

    public function toHexadecimal(): string
    {
        $hex = $this->red.$this->green.$this->blue;

        if (!$this->alpha->atMaximum()) {
            $hex .= $this->alpha()->toHexadecimal();
        }

        return $hex;
    }

    public function toHSLA(): HSLA
    {
        if ($this->hsla instanceof HSLA) {
            return $this->hsla;
        }

        $red = $this->red->toInt() / 255;
        $green = $this->green->toInt() / 255;
        $blue = $this->blue->toInt() / 255;

        $max = max($red, $green, $blue);
        $min = min($red, $green, $blue);
        $lightness = ($max + $min) / 2;

        if ($max === $min) {
            return $this->hsla = new HSLA(
                new Hue(0),
                new Saturation(0),
                new Lightness((int) round($lightness * 100)),
                $this->alpha
            );
        }

        $delta = $max - $min;
        $saturation = $lightness > 0.5 ? $delta / (2 - $max - $min) : $delta / ($max + $min);

        switch ($max) {
            case $red:
                $hue = (($green - $blue) / $delta) + ($green < $blue ? 6 : 0);
                break;
            case $green:
                $hue = (($blue - $red) / $delta) + 2;
                break;
            case $blue:
                $hue = (($red - $green) / $delta) + 4;
                break;
        }

        $hue *= 60;

        return $this->hsla = new HSLA(
            new Hue((int) round($hue)),
            new Saturation((int) round($saturation * 100)),
            new Lightness((int) round($lightness * 100)),
            $this->alpha
        );
    }

    public function toCMYKA(): CMYKA
    {
        if ($this->cmyka instanceof CMYKA) {
            return $this->cmyka;
        }

        $red = $this->red->toInt() / 255;
        $green = $this->green->toInt() / 255;
        $blue = $this->blue->toInt() / 255;

        if (
            $this->red->atMinimum() &&
            $this->green->atMinimum() &&
            $this->blue->atMinimum()
        ) {
            return $this->cmyka = new CMYKA(
                new Cyan(0),
                new Magenta(0),
                new Yellow(0),
                new Black(100),
                $this->alpha
            );
        }

        $black = min(1 - $red, 1 - $green, 1 - $blue);
        $cyan = (1 - $red - $black) / (1 - $black);
        $magenta = (1 - $green - $black) / (1 - $black);
        $yellow = (1 - $blue - $black) / (1 - $black);

        return $this->cmyka = new CMYKA(
            new Cyan((int) round($cyan * 100)),
            new Magenta((int) round($magenta * 100)),
            new Yellow((int) round($yellow * 100)),
            new Black((int) round($black * 100)),
            $this->alpha
        );
    }

    public function toRGBA(): self
    {
        return $this;
    }

    public function __toString(): string
    {
        return $this->string;
    }
}
