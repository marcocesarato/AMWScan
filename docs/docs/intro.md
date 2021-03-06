---
sidebar_position: 1
slug: /
---

# Intro

## PHP Antimalware Scanner

PHP Antimalware Scanner is a free tool to scan PHP files and analyze your project to find any malicious code inside it.

It provides an interactive text terminal console interface to scan a file, or all files in a given directory, and find
PHP code files that seem to contain malicious code. When a probable malware is detected, will be asked what action to
take (like add to whitelist, delete files, try clean infected code etc...).

The package can also scan the PHP files in a report mode, so without interact and outputting anything to the terminal
console. In that case the results will be stored in a report file in html (default) or text format.

This scanner can work on your own php projects and on a lot of others platform using the right combinations of
configurations (ex. using *lite* flag can help to find less false positivity).

:::caution

Remember that you will be solely responsible for any damage to your computer system or loss of data that results from
such activities. You are solely responsible to adequate protection and backup of the data before execute the scanner.

:::

## How it works

It checks for all lines of code on every file of the given directory for **Dangerous functions**, **Exploits** and **
Signatures**. When a probable malware is detected, will be asked what action to take, or it'll just print on your report
file.

### Dangerous functions

Dangerous functions are php functions that could damage your system. As example `system` or `shell_exec` could execute
malicious native code on your environment.

### Signatures

Signatures are specific patterns that allows to recognize malicious threats, such as a known malicious instruction
sequences used by families of malware.

### Exploits

Exploits are very similar to **signatures** but have a more common pattern and for this they can find false positives
much more easily. So often they are reported as a warning to the user rather than a certain malware.

