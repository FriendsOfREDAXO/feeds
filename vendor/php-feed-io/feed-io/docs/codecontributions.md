# Code Quality

This project uses PHPStan for static analysis and Rector for automated code refactoring to maintain high code quality standards.

## PHPStan - Static Analysis

PHPStan analyzes the code for potential issues and type safety problems. The configuration is stored in `phpstan.neon.dist`.

### Generate a new baseline
When introducing PHPStan to legacy code or after major refactoring, you can create a baseline to suppress existing errors:
```bash
vendor/bin/phpstan analyse --generate-baseline
```

### Run analysis
To analyze the codebase and find issues:
```bash
vendor/bin/phpstan analyse
```

### Configuration
- Configuration file: `phpstan.neon.dist`
- Current analysis level: 5
- Baseline file: `phpstan-baseline.neon` (suppresses known issues)

## Rector - Automated Refactoring

Rector automatically modernizes PHP code and applies coding standards. The configuration is stored in `rector.php`.

### Preview changes (recommended first step)
Run a dry-run to see what changes Rector would make without actually modifying files:
```bash
vendor/bin/rector process --dry-run
```

### Apply changes
Apply the refactoring rules to actually modify the code:
```bash
vendor/bin/rector process
```

### Debug mode
To see detailed information about what Rector is doing:
```bash
vendor/bin/rector process --dry-run --debug
```

### Finding new rules
To discover additional Rector rules for your project, visit: https://getrector.com/find-rule

## Workflow

1. **Before making changes**: Run `vendor/bin/phpstan analyse` to check current code quality
2. **Use Rector for improvements**: Run `vendor/bin/rector process --dry-run` to preview automated fixes
3. **Apply safe changes**: Run `vendor/bin/rector process` to apply the changes
4. **Verify with tests**: Run `make test` to ensure changes don't break functionality
5. **Final analysis**: Run `vendor/bin/phpstan analyse` to confirm improvements

## CI/CD Integration

Both PHPStan and Rector are integrated into the CI pipeline to ensure:
- No new PHPStan errors are introduced (beyond the baseline)
- Code follows modern PHP practices through Rector