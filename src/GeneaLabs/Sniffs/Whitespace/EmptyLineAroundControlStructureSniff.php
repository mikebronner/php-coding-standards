<?php

namespace Genealabs\PhpCodingStandards\GeneaLabs\Sniffs\Whitespace;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

class EmptyLineAroundControlStructureSniff implements Sniff
{
    protected $file;
    protected $stackPointer = -1;
    protected $tokens = [];

    public function register(): array
    {
        return [
            T_IF,
            T_ELSE,
            T_ELSEIF,
            T_WHILE,
            T_DO,
            T_FOR,
            T_FOREACH,
            T_SWITCH,
            T_TRY,
            T_CATCH,
            T_FINALLY,
        ];
    }

    public function process($phpcsFile, $stackPointer): void
    {
        $this->file = $phpcsFile;
        $this->stackPointer = $stackPointer;
        $this->tokens = $phpcsFile->getTokens();

        $this->lintBeforeControlStructure();
        $this->lintAfterControlStructure();
    }

    protected function isFollowingContentPermitted($content): bool
    {
        $permittedControlStructures = [
            "if" => [
                "else",
                "elseif",
            ],
            "elseif" => [
                "else",
            ],
            "try" => [
                "catch",
            ],
            "catch" => [
                "finally",
            ],
        ];

        return in_array($content, $permittedControlStructures[$this->tokens[$this->stackPointer]["content"]] ?? [])
            ?? false;
    }

    protected function isPreceedingContentPermitted(): bool
    {
        $content = $this->tokens[$this->stackPointer]["content"];
        $ignoredControlStructures = [
            "else",
            "elseif",
            "catch",
            "finally",
        ];

        return ! in_array($content, $ignoredControlStructures);
    }

    protected function lintAfterControlStructure(): void
    {
        $whitespace = "";
        $index = $this->file->findNext(T_WHITESPACE, $this->tokens[$this->stackPointer]["scope_closer"]);

        while ($this->tokens[$index]["type"] === "T_WHITESPACE") {
            $whitespace .= $this->tokens[$index]["content"];
            $index++;
        }

        $newlines = substr_count($whitespace, "\n");
        $nextToken = $this->tokens[$index]["content"];

        if (
            $newlines < 2
            && $nextToken !== "}"
            && ! $this->isFollowingContentPermitted($nextToken)
        ) {
            $this->file->addWarning(
                "Missing empty line after control structure.",
                $this->tokens[$this->stackPointer]["scope_closer"],
                "EmptyLineAfterControlStructure"
            );
        }

        if (
            $newlines >= 2
            && $nextToken === "}"
        ) {
            $this->file->addWarning(
                "There should be no empty lines after control "
                    . "structures at the end of a code block.",
                $this->tokens[$this->stackPointer]["scope_closer"],
                "NoEmptyLinesAfterControlStructureAtEndOfBlock"
            );
        }
    }

    protected function lintBeforeControlStructure(): void
    {
        if (! $this->isPreceedingContentPermitted()) {
            return;
        }

        $whitespace = "";
        $index = $this->stackPointer - 1;

        while ($this->tokens[$index]["type"] === "T_WHITESPACE") {
            $whitespace .= $this->tokens[$index]["content"];
            $index--;
        }

        $previousContent = $this->tokens[$index]["content"];
        $newlines = substr_count($previousContent . $whitespace, "\n");

        if (
            $newlines < 2
            && $previousContent !== "{"
        ) {
            $this->file->addWarning(
                "Missing empty line before control structure.",
                $this->stackPointer,
                "EmptyLineBeforeControlStructure"
            );
        }

        if (
            $newlines >= 2
            && $previousContent === "{"
        ) {
            $this->file->addWarning(
                "There should be no empty line(s) at the beginning of a block before control structure.",
                $this->stackPointer,
                "NoEmptyLineBeforeControlStructureAtBeginningOfBlock"
            );
        }
    }
}
