---
sidebar_position: 1
---

# Programmatically

You can also use this library programmatically using **AMWScan\Scanner** class methods.

:::info 

On programmatically silent mode and auto skip are automatically enabled.

:::

## Example

```php
use AMWScan\Scanner;

$app = new Scanner();
$report = $app->setPathScan("my/path/to/scan")
              ->enableBackups()
              ->setPathBackups("/my/path/backups")
              ->enableLiteMode()
              ->setAutoClean()
              ->run();
```

**Result object**

```
object(stdClass) (7) {
  ["scanned"]    => int(0)
  ["detected"]   => int(0)
  ["removed"]    => array(0) {}
  ["ignored"]    => array(0) {}
  ["edited"]     => array(0) {}
  ["quarantine"] => array(0) {}
  ["whitelist"]  => array(0) {}
}
```
