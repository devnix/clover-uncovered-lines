# Clover Uncovered Lines

[![Coverage Status](https://coveralls.io/repos/github/devnix/clover-uncovered-lines/badge.svg?branch=main)](https://coveralls.io/github/devnix/clover-uncovered-lines?branch=main)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fdevnix%2Fclover-uncovered-lines%2Fmain)](https://dashboard.stryker-mutator.io/reports/github.com/devnix/clover-uncovered-lines/main)

A simple CLI tool to parse Clover XML coverage reports and display uncovered lines in a human(-and-llm)-readable format.

## Installation

Install via Composer:

```bash
composer require --dev devnix/clover-uncovered-lines
```

## Usage

Run the tool by passing the path to your Clover XML coverage file:

```bash
vendor/bin/clover-uncovered-lines path/to/clover.xml
```

### Example Output

When there are uncovered lines:

```
Uncovered lines:

src/Example.php
  Lines 15-18
  Line 42 (method)

src/AnotherFile.php
  Line 23

Summary: 5 uncovered lines in 2 file(s)
```

When all lines are covered:

```
✓ All lines are covered!
```

## Exit Codes

- `0` - All lines are covered
- `1` - Uncovered lines found or error occurred

## Features

- Parses Clover XML coverage reports
- Groups consecutive uncovered lines into ranges for readability
- Identifies uncovered methods
- Auto-detects project root from XML paths
- Provides summary statistics

## Requirements

- PHP 8.4 or higher

## License

MIT

## Author

Pablo Largo Mohedano (devnix.code@gmail.com)
