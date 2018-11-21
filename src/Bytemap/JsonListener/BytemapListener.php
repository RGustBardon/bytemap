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
    private const STATE_DOCUMENT_STARTED = 'DocumentStarted';
    private const STATE_AWAITING_VALUE = 'AwaitingValue';
    private const STATE_DOCUMENT_ENDED = 'DocumentEnded';

    private const TRANSITIONS = [
        self::STATE_INITIAL => self::STATE_DOCUMENT_STARTED,
        self::STATE_DOCUMENT_STARTED => self::STATE_AWAITING_VALUE,
        self::STATE_AWAITING_VALUE => self::STATE_DOCUMENT_ENDED,
        self::STATE_DOCUMENT_ENDED => self::STATE_INITIAL,
    ];

    private $setter;

    private $key;
    private $state = self::STATE_INITIAL;

    public function __construct(callable $setter)
    {
        $this->setter = $setter;
    }

    public function startDocument()
    {
        $this->transition(self::STATE_DOCUMENT_STARTED);
    }

    public function endDocument()
    {
        $this->transition(self::STATE_INITIAL);
    }

    public function startObject()
    {
        $this->transition(self::STATE_AWAITING_VALUE);
    }

    public function endObject()
    {
        $this->transition(self::STATE_DOCUMENT_ENDED);
    }

    public function startArray()
    {
        $this->transition(self::STATE_AWAITING_VALUE);
    }

    public function endArray()
    {
        $this->transition(self::STATE_DOCUMENT_ENDED);
    }

    public function key($key)
    {
        $intKey = (int) $key;
        $this->key = ((string) $intKey === $key) ? $intKey : $key;
    }

    public function value($value)
    {
        ($this->setter)($this->key, $value);
        $this->key = null;
    }

    public function whitespace($whitespace)
    {
        // Ignored.
    }

    /**
     * @param mixed $newState
     */
    private function transition($newState): void
    {
        if (self::TRANSITIONS[$this->state] === $newState) {
            $this->state = $newState;
        } else {
            $message = \sprintf('Invalid JSON transition (%s to %s).', $this->state, $newState);

            throw new \UnexpectedValueException($message);
        }
    }
}
