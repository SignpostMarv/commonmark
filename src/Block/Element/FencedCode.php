<?php

/*
 * This file is part of the league/commonmark package.
 *
 * (c) Colin O'Dell <colinodell@gmail.com>
 *
 * Original code based on the CommonMark JS reference parser (https://bitly.com/commonmark-js)
 *  - (c) John MacFarlane
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace League\CommonMark\Block\Element;

use League\CommonMark\ContextInterface;
use League\CommonMark\Cursor;
use League\CommonMark\Util\RegexHelper;

class FencedCode extends AbstractBlock
{
    /**
     * @var string
     */
    protected $info;

    /**
     * @var int
     */
    protected $length;

    /**
     * @var string
     */
    protected $char;

    /**
     * @var int
     */
    protected $offset;

    /**
     * @param int    $length
     * @param string $char
     * @param int    $offset
     */
    public function __construct($length, $char, $offset)
    {
        parent::__construct();

        $this->length = $length;
        $this->char = $char;
        $this->offset = $offset;
    }

    /**
     * @return string
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * @return string[]
     */
    public function getInfoWords()
    {
        /**
         * @var string[] $out
         */
        $out = array_filter((array) preg_split('/\s+/', $this->info), 'is_string');

        return $out;
    }

    /**
     * @return string
     */
    public function getChar()
    {
        return $this->char;
    }

    /**
     * @param string $char
     *
     * @return $this
     */
    public function setChar($char)
    {
        $this->char = $char;

        return $this;
    }

    /**
     * @return int
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * @param int $length
     *
     * @return $this
     */
    public function setLength($length)
    {
        $this->length = $length;

        return $this;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @param int $offset
     *
     * @return $this
     */
    public function setOffset($offset)
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Returns true if this block can contain the given block as a child node
     *
     * @param AbstractBlock $block
     *
     * @return bool
     */
    public function canContain(AbstractBlock $block)
    {
        return false;
    }

    /**
     * Returns true if block type can accept lines of text
     *
     * @return bool
     */
    public function acceptsLines()
    {
        return true;
    }

    /**
     * Whether this is a code block
     *
     * @return bool
     */
    public function isCode()
    {
        return true;
    }

    public function matchesNextLine(Cursor $cursor)
    {
        if ($this->length === -1) {
            if ($cursor->isBlank()) {
                $this->lastLineBlank = true;
            }

            return false;
        }

        // Skip optional spaces of fence offset
        $cursor->match('/^ {0,' . $this->offset . '}/');

        return true;
    }

    public function finalize(ContextInterface $context, $endLineNumber)
    {
        parent::finalize($context, $endLineNumber);

        // first line becomes info string
        $this->info = RegexHelper::unescape(trim($this->strings->first()));

        if ($this->strings->count() === 1) {
            $this->finalStringContents = '';
        } else {
            $this->finalStringContents = implode("\n", $this->strings->slice(1)) . "\n";
        }
    }

    /**
     * @param ContextInterface $context
     * @param Cursor           $cursor
     */
    public function handleRemainingContents(ContextInterface $context, Cursor $cursor)
    {
        /** @var FencedCode $container */
        $container = $context->getContainer();

        // check for closing code fence
        if ($cursor->getIndent() <= 3 && $cursor->getNextNonSpaceCharacter() === $container->getChar()) {
            $match = RegexHelper::matchAll('/^(?:`{3,}|~{3,})(?= *$)/', $cursor->getLine(), $cursor->getNextNonSpacePosition());
            if (strlen($match[0]) >= $container->getLength()) {
                // don't add closing fence to container; instead, close it:
                $this->setLength(-1); // -1 means we've passed closer

                return;
            }
        }

        $tip = $context->getTip();
        if ($tip instanceof AbstractBlock) {
            $tip->addLine($cursor->getRemainder());
        }
    }

    /**
     * @param Cursor $cursor
     * @param int    $currentLineNumber
     *
     * @return bool
     */
    public function shouldLastLineBeBlank(Cursor $cursor, $currentLineNumber)
    {
        return false;
    }
}
