<?php

namespace Genealabs\PhpCodingStandards\GeneaLabs\Sniffs\TypeHinting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

class ReturnTypeSniff implements Sniff
{
    protected $file;
    protected $stackPointer = -1;
    protected $tokens = [];

    public function register(): array
    {
        return [
            T_FUNCTION,
        ];
    }

    public function process($phpcsFile, $stackPointer): void
    {
        $this->file = $phpcsFile;
        $this->stackPointer = $stackPointer;
        $this->tokens = $this->file->getTokens();
        $this->lintMethodSignature();
    }

    protected function getMethodLine(): string
    {
        $line = "";
        $reachedEndOfLine = false;
        $index = $this->stackPointer;

        while (! $reachedEndOfLine) {
            $content = $this->tokens[$index]["content"];
            $line .= $content;
            $reachedEndOfLine = $content === "\n";
            $index++;
        }

        return $line;
    }

    protected function lintMethodSignature(): void
    {
        if ($this->file->getDeclarationName($this->stackPointer) === "__construct") {
            return;
        }

        if (strpos($this->getMethodLine(), ":") !== false) {
            $this->file->addError(
                "Missing return type.",
                $this->tokens[$this->stackPointer]["parenthesis_closer"],
                "MissingReturnType"
            );
        }
    }
}
