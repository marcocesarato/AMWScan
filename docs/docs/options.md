---
sidebar_position: 5
---

# Options

:::note 

To open files with nano or vim run the scripts with **php -d disable_functions=''**

:::

## Arguments

```
<path>   - Define the path of the file or directory to scan
```

## Flags

```
--auto-clean                                   - Auto clean code (without confirmation, use with caution)
--auto-clean-line                              - Auto clean line code (without confirmation, use with caution)
--auto-delete                                  - Auto delete infected (without confirmation, use with caution)
--auto-prompt <prompt>                         - Set auto prompt command .
                                                 ex. --auto-prompt="delete" or --auto-prompt="1" (alias of auto-delete)
--auto-quarantine                              - Auto quarantine
--auto-skip                                    - Auto skip
--auto-whitelist                               - Auto whitelist (if you sure that source isn't compromised)
--backup|-b                                    - Make a backup of every touched files
--defs                                         - Get default definitions exploit and functions list
--defs-exploits                                - Get default definitions exploits list
--defs-functions                               - Get default definitions functions lists
--defs-functions-encoded                       - Get default definitions functions encoded lists
--disable-cache|--no-cache                     - Disable Cache
--disable-checksum|--no-checksum|--no-verify   - Disable checksum verifying for platforms/frameworks
--disable-colors|--no-colors|--no-color        - Disable CLI colors
--disable-report|--no-report                   - Disable report generation
--exploits <exploits>                          - Filter exploits
--filter-paths|--filter-path <paths>           - Filter path/s, for multiple value separate with comma.
                                                 Wildcards are enabled ex. /path/*/htdocs or /path/*.php
--functions <functions>                        - Define functions to search
--help|-h|-?                                   - Check only functions and not the exploits
--ignore-paths|--ignore-path <paths>           - Ignore path/s, for multiple value separate with comma.
                                                 Wildcards are enabled ex. /path/*/cache or /path/*.log
--limit <limit>                                - Set file mapping limit
--lite|-l                                      - Running on lite mode help to have less false positive on WordPress and others
                                                 platforms enabling exploits mode and removing some common exploit pattern
--log <path>                                   - Write a log file on the specified file path
                                                 [default: ./scanner.log]
--max-filesize <filesize>                      - Set max filesize to scan
                                                 [default: -1]
--offset <offset>                              - Set file mapping offset
--only-exploits|-e                             - Check only exploits and not the functions
--only-functions|-f                            - Check only functions and not the exploits
--only-signatures|-s                           - Check only functions and not the exploits.
                                                 This is recommended for WordPress or others platforms
--path-backups <path>                          - Set backups path directory.
                                                 Is recommended put files outside the public document path
                                                 [default: /scanner-backups/]
--path-logs <path>                             - Set quarantine log file
                                                 [default: ./scanner.log]
--path-quarantine <path>                       - Set quarantine path directory.
                                                 Is recommended put files outside the public document path
                                                 [default: ./scanner-quarantine/]
--path-report <path>                           - Set report log file path and name.
                                                 Note that name will be appended with .log or .html extension.
                                                 [default: ./scanner-report.html]
--path-whitelist <path>                        - Set whitelist file
                                                 [default: ./scanner-whitelist.json]
--report-format <format>                       - Report format (html|txt)
--report|-r                                    - Report scan only mode without check and remove malware (like --auto-skip).
                                                 It also write a report with all malware paths found
--scan-all|--all                               - Check all files, regardless of extension
--silent                                       - No output and prompt
--update|-u                                    - Update to last version
--version|-v                                   - Get version number
--whitelist-only-path                          - Check on whitelist only file path and not line number
```