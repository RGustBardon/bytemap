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

use JsonStreamingParser\Listener\ListenerInterface;

/**
 * @author Robert Gust-Bardon <robert@gust-bardon.org>
 */
class BytemapListener implements ListenerInterface
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

    public function startDocument(): void
    {
        $this->transition(self::STATE_DOCUMENT_STARTED);
    }

    public function endDocument(): void
    {
        $this->transition(self::STATE_INITIAL);
    }

    public function startObject(): void
    {
        $this->transition(self::STATE_AWAITING_VALUE);
    }

    public function endObject(): void
    {
        $this->transition(self::STATE_DOCUMENT_ENDED);
    }

    public function startArray(): void
    {
        $this->transition(self::STATE_AWAITING_VALUE);
    }

    public function endArray(): void
    {
        $this->transition(self::STATE_DOCUMENT_ENDED);
    }

    public function key(string $key): void
    {
        $intKey = (int) $key;
        $this->key = ((string) $intKey === $key) ? $intKey : $key;
    }

    public function value($value): void
    {
        ($this->setter)($this->key, $value);
        $this->key = null;
    }

    public function whitespace(string $whitespace): void
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
