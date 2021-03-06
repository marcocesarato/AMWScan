---
sidebar_position: 7
---

# How to update

To update the scanner, and then all new malware definitions, you can use the command option, or you can just re-download it from GitHub (like on the installation process).

## Autoupdate

Run the console command using the option `--update` or `-u` (filename will be kept):

```shell
php amwscan --update
```

## Download from browser

As alternative, you can go on GitHub page and press on Releases tab
or [download from here](https://raw.githubusercontent.com/marcocesarato/PHP-Antimalware-Scanner/master/dist/scanner)

## Download from console

Another alternative is to run this command from console (scanner will be download on your current directory):

```shell
wget https://raw.githubusercontent.com/marcocesarato/PHP-Antimalware-Scanner/master/dist/scanner --no-check-certificate
```