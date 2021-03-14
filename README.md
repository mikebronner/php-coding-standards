# GeneaLabs PHP Coding Standards
Custom PHPCS sniffs that support all our coding standards.

## Custom Rules
### Type Hinting
- Method Parameter Type Hints
- Method Return Type Hints

### Whitespace
- Empty Lines Acound Control Structures
- Empyt Line Before Returns
- No Mutliple Consecutive Empty Lines

## Adopted Rules
- PSR1
- PSR2
- PSR12
    - except: PSR12.Classes.ClassInstantiation.MissingParentheses, as we want to new up classes without parenthesis.
- Internal.NoCodeFound
- Zend.Files.ClosingTag
- Zend.NamingConventions
    - except: Zend.NamingConventions.ValidVariableName.PrivateNoUnderscore, as we want all variables and properties to be in camelCase.
