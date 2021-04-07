<?php

namespace Genealabs\PhpCodingStandards\GeneaLabs\Sniffs\Whitespace;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

class EmptyLineBeforeReturnSniff implements Sniff
{
    protected $file;
    protected $stackPointer = -1;
    protected $tokens = [];

    public function register(): array
    {

        return [
            T_RETURN,
        ];
    }

    public function process($phpcsFile, $stackPointer): void
    {
        $this->file = $phpcsFile;
        $this->stackPointer = $stackPointer;
        $this->tokens = $this->file->getTokens();
        $this->lintBeforeReturn();
    }

    protected function lintBeforeReturn(): void
    {
        $whitespace = "";
        $index = $this->stackPointer - 1;

        while ($this->tokens[$index]["type"] === "T_WHITESPACE") {
            $whitespace .= $this->tokens[$index]["content"];
            $index--;
        }

        $previousContent = $this->tokens[$index]["content"];
        $newlines = substr_count($whitespace, "\n");

        if (
            $newlines < 2
            && $previousContent !== "{"
        ) {
            $this->file->addWarning(
                "Missing empty line before return.",
                $this->stackPointer,
                "EmptyLineBeforeReturn"
            );
        }

        if (
            ($newlines >= 2
                && $previousContent === "{")
            || ($newlines > 2
                && $previousContent !== "{")
        ) {
            $extraNewLines = $newlines - 1;
            $this->file->addWarning(
                "{$extraNewLines} extraneous empty line(s) before return at beginning of block.",
                $this->stackPointer,
                "NoEmptyLineBeforeReturnAtBeginningOfBlock"
            );
        }
    }
}
