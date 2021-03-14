# GeneaLabs PHP Coding Standards
Custom PHPCS sniffs that support all our coding standards.

## Installation
There are several ways to install this ruleset:
1. Add the following to your composer.json scripts section:
  ```
  "post-update-cmd" : [
      "tools/phpcs --config-set installed_paths vendor/genealabs/php-coding-standards/src/GeneaLabs"
  ]
  ```
2. Or add the following to your phpcs.xml file:
  ```
  <ruleset>
      <rule ref="./vendor/genealabs/php-coding-standards/src/GeneaLabs/ruleset.xml"/>
  </ruleset>
  ```

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
