<?php

namespace Genealabs\PhpCodingStandards\GeneaLabs\Sniffs\TypeHinting;

use Exception;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;

class MethodParameterTypeHintSniff implements Sniff
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

    protected function doesInterfaceParameterNotHaveTypeHint(
        $parameterName,
        $parameterIndex
    ): bool {
        $methodName = $this->file->getDeclarationName($this->stackPointer);
        $parameterName = trim($parameterName, "$ ");
        $className = $this->getFullyQualifiedClassName();
        $interfaces = [];

        try {
            $reflectedClass = new ReflectionClass($className);
        } catch (Exception $exception) {
            // continue
        } finally {
            if ($reflectedClass ?? false) {
                $interfaces = $reflectedClass->getInterfaces();
            }
        }

        foreach ($interfaces as $interface) {
            $reflectedInterface = new ReflectionClass($interface->name);
            $methods = $reflectedInterface->getMethods();

            foreach ($methods as $method) {
                if ($method->name !== $methodName) {
                    continue;
                }

                $reflectedMethod = new ReflectionMethod($interface->name, $method->name);
                $parameters = $reflectedMethod->getParameters();

                foreach ($parameters as $key => $parameter) {
                    if ($key !== $parameterIndex) {
                        continue;
                    }

                    $reflectedParameter = new ReflectionParameter([$interface->name, $method->name], $key);
                    $type = $reflectedParameter->getType();

                    if (! $type) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    protected function getFullyQualifiedClassName(): string
    {
        $classToken = $this->file->findPrevious(T_CLASS, $this->stackPointer);
        $className = "";

        if ($classToken) {
            $namespaceToken = $this->file->findPrevious(T_NAMESPACE, $classToken);
            $namespace = "";
            $index = $namespaceToken;
            $reachedEndOfLine = false;

            while (! $reachedEndOfLine) {
                $content = $this->tokens[$index]["content"];
                $namespace .= $content;
                $index++;
                $reachedEndOfLine = $content === "\n";
            }

            $namespace = str_replace("namespace", "", $namespace);
            $namespace = str_replace(";", "", $namespace);
            $namespace = trim($namespace);
            $className = $namespace . "\\" . $this->file->getDeclarationName($classToken);
        }

        return $className;
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

        $matches = [];
        preg_match('/\((.*?)\)/', $line, $matches);
        $line = $matches[1]
            ?? "";

        return $line;
    }

    protected function lintMethodSignature(): void
    {
        $parameters = $this->getMethodLine();

        if (! $parameters) {
            return;
        }

        $parameters = explode(",", $parameters);

        $index = $this->stackPointer;

        foreach ($parameters as $parameterIndex => $parameter) {
            $parameter = trim($parameter);

            if ($parameter[0] === "$") {
                $index = $this->stackPointer;
                $parameter = trim(explode("=", $parameter)[0]);

                if (! $this->doesInterfaceParameterNotHaveTypeHint($parameter, $parameterIndex)) {
                    while ($index < $this->tokens[$this->stackPointer]["parenthesis_closer"]) {
                        if ($parameter == $this->tokens[$index]["content"]) {
                            $this->file->addError(
                                "Missing parameter type hint.",
                                $index,
                                "MissingParameterTypeHint"
                            );
                        }

                        $index++;
                    }
                }
            }
        }
    }
}
