---
sidebar_position: 4
---

# Getting Started

## Scanning mode

The first think you need to decide is the strength, you need to calibrate your scan to find less false positive as possible during scanning but without miss real malware.
For this you can choose the aggression level.

The scanner permit to have some predefined modes:

| Mode                       | Alias | 🚀            | Description                                                                                                                                                                       |
| --------------------------- | ----- | -------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| None&nbsp;*(default)*            |   | 🔴        | Search for all functions, exploits and malware signs without any restrictions                                                                                                     |
| Only&nbsp;exploits   | `-e` | 🟠     | Search only for exploits definitions<br />Use flag: `--only-exploits`                                                                                                                                            |
| Lite&nbsp;mode          | `-l` | 🟡     | Search for exploits with some restrictions and malware signs *(on Wordpress and others platform could detect less false positivity)*<br />Use flag: `--lite`                                              |
| Only&nbsp;functions  | `-f`| 🟡     | Search only for functions *(on some obfuscated code functions couldn't be detected)* <br />Use flag: `--only-functions`                                                                                             |
| Only&nbsp;signatures | `-s` | 🟢      | Search only for malware signatures *(could be a good solution for Wordpress and others platform to detect less false positivity)*<br />Use flag: `--only-signatures`                                                 |

### Suggestions

If you are running the scanner on a Wordpress project or other popular platform use `--only-signatures` or `--lite` flag
to have check with less false positive but this could miss some dangerous exploits like `nano`.

#### Examples:

```shell
php -d disable_functions='' scanner -s
php -d disable_functions='' scanner -l
```

## Detection Options

When a malware is detected you will have the following choices (except when scanner is running in report
mode `--report`):

- Delete file `--auto-delete`
- Move to quarantine (*move to* `./scanner-quarantine`) `--auto-quarantine`
- Dry run evil code fixer *(try to infected fix code and confirm after a visual check)* `--auto-clean`
- Dry run evil line code fixer *(try to fix infected code and confirm after a visual check)* `--auto-clean-line`
- Open with vim (*need* `php -d disable_functions=''`)
- Open with nano (*need* `php -d disable_functions=''`)
- Add to whitelist (*add to* `./scanner-whitelist.json`)
- Show source
- Ignore `--auto-skip`

## Usage

```
Usage: amwscan [--lite|-a] [--help|-h|-?] [--log|-l <path>] [--backup|-b] [--offset
        <offset>] [--limit <limit>] [--report|-r] [--report-format <format>]
        [--version|-v] [--update|-u] [--only-signatures|-s] [--only-exploits|-e]
        [--only-functions|-f] [--defs] [--defs-exploits] [--defs-functions]
        [--defs-functions-enc] [--exploits <exploits>] [--functions <functions>]
        [--whitelist-only-path] [--max-filesize <filesize>] [--silent]
        [--ignore-paths|--ignore-path <paths>] [--filter-paths|--filter-path <paths>]
        [--auto-clean] [--auto-clean-line] [--auto-delete] [--auto-quarantine]
        [--auto-skip] [--auto-whitelist] [--auto-prompt <prompt>] [--path-whitelist
        <path>] [--path-backups <path>] [--path-quarantine <path>] [--path-logs <path>]
        [--path-report <path>] [--disable-colors|--no-colors|--no-color]
        [--disable-cache|--no-cache] [--disable-report|--no-report]
        [--scan-all|--all] [<path>]
```

## Examples

```shell
php amwscan ./mywebsite/http/ -l -s --only-exploits
php amwscan -s --max-filesize="5MB"
php amwscan -s -logs="/user/marco/scanner.log"
php amwscan --lite --only-exploits
php amwscan --exploits="double_var2" --functions="eval, str_replace"
php amwscan --ignore-paths="/my/path/*.log,/my/path/*/cache/*"
```