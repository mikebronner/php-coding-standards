<?php

namespace Genealabs\PhpCodingStandards\GeneaLabs\Sniffs\Whitespace;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

class MultipleEmptyLinesSniff implements Sniff
{
    protected File $file;
    protected int $stackPointer = -1;
    protected array $tokens = [];

    public function register(): array
    {
        return [
            T_WHITESPACE,
        ];
    }

    public function process(File $phpcsFile, $stackPointer): void
    {
        $this->file = $phpcsFile;
        $this->stackPointer = $stackPointer;
        $this->tokens = $this->file->getTokens();
        $this->lintBeforeLinebreak();
    }

    protected function lintBeforeLinebreak(): void
    {
        if ($this->tokens[$this->stackPointer]["content"] !== "\n") {
            return;
        }

        $whitespace = "";
        $index = $this->stackPointer - 1;

        while ($this->tokens[$index]["type"] === "T_WHITESPACE") {
            $whitespace .= $this->tokens[$index]["content"];
            $index--;
        }

        $newlines = substr_count($whitespace, "\n");

        if ($newlines > 1) {
            $this->file->addWarning(
                "Code should not have multiple consecutive empty lines.",
                $this->stackPointer,
                "MultipleConsecutiveEmptyLines"
            );
        }
    }
}
