# Cloud data model for iTop

This extension adds provider-neutral cloud inventory to iTop 3.3. It extends the
native `Cloud` and defines `CloudLan`, `CloudNatGateway`, `CloudVpnGateway` and `CloudNetworkLoadBalancer`.
Provider extensions can depend on `br-cloud-datamodel/0.3.0` and add fields, sample data or collectors independently.

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
all Cloud LANs, Cloud NAT and VPN gateways, and Cloud network load balancers.

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

## VPN gateways

Open a Cloud's **VPN gateways** tab to find its IPsec and WireGuard gateways.
Each detail page shows its protocol and public endpoint, plus the IKE version
or listen port when reported. **LAN attachments** opens the connected LANs
and their LAN-side host assignments. **WireGuard interface addresses** opens
the virtual interface assignments. Both host lists retain the reported IPv4
or IPv6 prefix separately. With TeemIP, the endpoint and host assignments
link to the customer's IP objects. Removing an assignment keeps its IP object.

### Gateway class decision

Both gateway classes inherit `name`, `org_id`, `description` and the iTop CI
lifecycle from `FunctionalCI`. Both use a required `cloud_id` and expose the
Cloud's name through `cloud_name`. Their provider IDs are stable across renames,
but their supported identity scopes differ.

- NAT reconciles by `cloud_id` and `provider_resource_id`. VPN reconciles
  by `org_id`, the provider-defined `source_namespace` and `provider_resource_id`.
- NAT links multiple public hosts. VPN has one public endpoint.
- Each class has explicit LAN attachments, unique per gateway and LAN,
  and stores host addresses and prefixes on those attachments. VPN also
  records protocol, IKE version, listen port and WireGuard interface hosts.
- Source attribution for each class and VPN provider metadata belong in
  the provider extension.

Only the Cloud reference, display name of the Cloud and the provider ID
have closely matching field definitions. The concrete classes keep their
own reconciliation, attachment and address rules. A shared parent would
move fields of existing NAT objects during Setup for limited reuse. This
additive model leaves the existing NAT class, tables, fields and relationships
in place as Setup installs the VPN classes.

## Network load balancers

Open a Cloud's **Network load balancers** tab to see its load balancers.
Each load balancer identifies its **Listener LAN** and **Backend LAN** within
that Cloud and lists the host addresses used on each side. The Cloud and
provider resource ID identify the load balancer across renames.

Each row represents a single host. Backend rows also hold the reported
prefix length. For example, `10.20.0.7/24` is stored as host `10.20.0.7`
with prefix length `24`. The base module stores the host literal, and the
TeemIP bridge links to the customer organization's IP address object.
Removing an assignment keeps the linked address in TeemIP.

## Development

See [local checks](.dev/tests/README.md) and [release preparation](scripts/README.md).
Run the Markdown and spelling checks from the repository root:

```sh
npm ci --cache .tmp/npm-cache
npm run lint:md
npm run lint:spelling
```
