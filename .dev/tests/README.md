# Extension checks

Run `php .dev/tests/run.php` with PHP 8.3, DOM and SimpleXML. Set `ITOP_SCHEMA_DIR` to a directory containing `extension.xsd` and the standalone `3.3/itop_design.xsd` from the [iTop schemas](https://rudnerbjoern.github.io/iTop-schema/). CI downloads these into `.tmp/schemas/`. The checks validate the descriptor and both module XML files and exercise release preparation in memory.

## Compiled model integration test

From the sibling iTop development environment, run the base installation first
and then the installation with TeemIP:

```powershell
.\scripts\tests\Invoke-Tests.ps1 -Suite Extensions `
    -ExtensionPath ../iTop-br-cloud-datamodel -ExtensionSuite Integration `
    -Profile Vanilla

.\scripts\tests\Invoke-Tests.ps1 -Suite Extensions `
    -ExtensionPath ../iTop-br-cloud-datamodel -ExtensionSuite Integration `
    -Profile Vanilla -DependencyPath ./extensions/teemip-core-ip-mgmt
```

This uses the reserved `itop33-tests` Docker project with a disposable database.
It checks the installed module set, class inheritance, external keys and
mandatory image fields against the compiled iTop and TeemIP models. It checks provider identifiers, LAN and NAT gateway identity within each Cloud and the Cloud list of TeemIP blocks and subnets.
