<?php

declare(strict_types=1);

/*
 * This file is part of the Bytemap package.
 *
 * (c) Robert Gust-Bardon <robert@gust-bardon.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bytemap\JsonListener;

use JsonStreamingParser\Listener;

/**
 * @author Robert Gust-Bardon <robert@gust-bardon.org>
 */
class BytemapListener implements Listener
{
    private const STATE_INITIAL = 'Initial';
    private const STATE_BEFORE_OUTER_ARRAY = 'BeforeOuterArray';
    private const STATE_BEFORE_DEFAULT_ITEM = 'BeforeDefaultItem';
    private const STATE_BEFORE_ITEMS = 'BeforeItems';
    private const STATE_BEFORE_ITEM = 'BeforeItem';
    private const STATE_AFTER_ITEMS = 'AfterItems';
    private const STATE_AFTER_OUTER_ARRAY = 'AfterOuterArray';

    private const TRANSITIONS = [
        self::STATE_INITIAL => [self::STATE_BEFORE_OUTER_ARRAY],
        self::STATE_BEFORE_OUTER_ARRAY => [self::STATE_BEFORE_DEFAULT_ITEM],
        self::STATE_BEFORE_DEFAULT_ITEM => [self::STATE_BEFORE_ITEMS],
        self::STATE_BEFORE_ITEMS => [self::STATE_BEFORE_ITEM, self::STATE_AFTER_ITEMS],
        self::STATE_BEFORE_ITEM => [self::STATE_BEFORE_ITEM, self::STATE_AFTER_ITEMS],
        self::STATE_AFTER_ITEMS => [self::STATE_AFTER_OUTER_ARRAY],
        self::STATE_AFTER_OUTER_ARRAY => [self::STATE_INITIAL],
    ];

    private $closure;

    private $key;
    private $state = self::STATE_INITIAL;

    public function __construct(\Closure $closure)
    {
        $this->closure = $closure;
    }

    public function startDocument()
    {
        $this->transition(self::STATE_BEFORE_OUTER_ARRAY);
    }

    public function endDocument()
    {
        $this->transition(self::STATE_INITIAL);
    }

    public function startObject()
    {
        $this->transition(self::STATE_BEFORE_ITEM);
    }

    public function endObject()
    {
        $this->transition(self::STATE_AFTER_ITEMS);
    }

    public function startArray()
    {
        switch ($this->state) {
            case self::STATE_BEFORE_OUTER_ARRAY:
                $this->transition(self::STATE_BEFORE_DEFAULT_ITEM);

                break;
            case self::STATE_BEFORE_ITEMS:
                $this->transition(self::STATE_BEFORE_ITEM);

                break;
            default:
                throw new \UnexpectedValueException('Bytemap: unexpected array in '.$this->state);
        }
    }

    public function endArray()
    {
        switch ($this->state) {
            case self::STATE_BEFORE_ITEM:
                $this->transition(self::STATE_AFTER_ITEMS);

                break;
            case self::STATE_AFTER_ITEMS:
                $this->transition(self::STATE_AFTER_OUTER_ARRAY);

                break;
            default:
                throw new \UnexpectedValueException('Bytemap: unexpected array end in '.$this->state);
        }
    }

    public function key($key)
    {
        $this->transition(self::STATE_BEFORE_ITEM);
        $this->key = $key;
    }

    public function value($value)
    {
        if (self::STATE_BEFORE_DEFAULT_ITEM === $this->state) {
            $this->transition(self::STATE_BEFORE_ITEMS);
        } else {
            $this->transition(self::STATE_BEFORE_ITEM);
        }
        ($this->closure)($value, $this->key);
        $this->key = null;
    }

    public function whitespace($whitespace)
    {
        // Ignored.
    }

    private function transition($newState): void
    {
        if (!\in_array($newState, self::TRANSITIONS[$this->state], true)) {
            $message = \sprintf('Bytemap: invalid JSON transition (%s to %s).', $this->state, $newState);

            throw new \UnexpectedValueException($message);
        }

        $this->state = $newState;
    }
}
