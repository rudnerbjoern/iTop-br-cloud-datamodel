# Cloud data model for iTop

This extension adds provider-neutral cloud inventory to iTop 3.3. It extends the
native `Cloud` and defines `CloudLan` and `CloudNatGateway`. Provider extensions can depend on
`br-cloud-datamodel/0.1.0` and add fields, sample data or collectors independently.

## Installation

Extract the release ZIP and place its `br-cloud-datamodel/` bundle (including
`extension.xml` and both module directories) under the iTop `extensions/`
directory. Run iTop Setup with Virtualization Management and Container
Management selected. Setup selects the optional bridge when TeemIP IP
Management 3.3.1 or later is installed.

The Cloud details form includes an **IP blocks and subnets** list
(`ip_objects_list`). Add any number of existing TeemIP blocks and subnets.
The selector accepts descendants of `IPBlock` and `IPSubnet`, including IPv6
types when IPv6 Management is installed. Removing an entry unlinks it from the
Cloud and keeps the IP object in TeemIP.

The link class `lnkCloudToIPObject` identifies each assignment by `cloud_id`
and `ipobject_id`. Each pair can occur once. Manual links have an empty
`source_key`. An importer can set this field when creating a link to
distinguish its own assignments from those maintained by other users or sources.

## Clouds and their LANs

Open **Configuration management > Overview > Virtualization** to browse
all Cloud LANs and Cloud NAT gateways.

`Cloud.provider_resource_id` is the identifier assigned by the provider.
The field is optional for manually maintained Clouds. Collectors must resolve
clouds within the provider's identifier scope. Providers that reuse identifiers
between accounts need to include their account context during reconciliation.

Each Cloud has a **LANs** tab listing its `CloudLan` objects. A LAN inherits
`name` and `org_id` from `FunctionalCI`. Its `cloud_id` references the
parent Cloud. The combination of parent and `provider_resource_id` uniquely
identifies the LAN. The `public` flag describes the provider's classification
of the LAN as public or private.

## NAT gateways

The **NAT gateways** tab on a Cloud lists its `CloudNatGateway` objects. Each
gateway is a `FunctionalCI` with a name and an organization. It belongs to one
Cloud through `cloud_id` and has a required `provider_resource_id`. The same
identifier can occur in different Clouds. Renaming a gateway preserves its
identity. This inventory is available with the base module alone.

Open **LAN attachments** on a gateway and select a Cloud LAN to see its
**Gateway addresses** and their reported prefix lengths. In the Frankfurt
preview, LAN 7 has `10.20.0.1/24` and `10.21.0.1/25` on the same
attachment. The **Public IP addresses** tab lists the gateway's public
hosts, including `203.0.113.10` and `203.0.113.11` in the preview.

Each assignment has one **Host address**. With the base module it stores
the IP literal in `address`. The TeemIP bridge replaces that field with a
required `ipaddress_id` reference to an IP address in the gateway's
organization. The address can occur once per gateway (public) or per LAN
attachment (private). Each row's name shows the host. The assignment details
provide the link to the TeemIP record when the bridge is installed.

## Development

See [local checks](.dev/tests/README.md) and [release preparation](scripts/README.md).
Run the Markdown and spelling checks from the repository root:

```sh
npm ci --cache .tmp/npm-cache
npm run lint:md
npm run lint:spelling
```
