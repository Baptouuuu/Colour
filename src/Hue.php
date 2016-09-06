<?php
declare(strict_types = 1);

namespace Innmind\Colour;

use Innmind\Colour\Exception\InvalidValueRangeException;

final class Hue
{
    private $value;

    public function __construct(int $value)
    {
        if ($value < 0 || $value > 359) {
            throw new InvalidValueRangeException;
        }

        $this->value = $value;
    }

    public function rotateBy(int $degrees): self
    {
        $degrees = $this->value + $degrees;

        if ($degrees < 0) {
            $degrees = 360 + $degrees;
        } else if ($degrees > 359) {
            $degrees = $degrees - 360;
        }

        return new self($degrees);
    }

    public function opposite(): self
    {
        return $this->rotateBy(180);
    }

    public function toInt(): int
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
