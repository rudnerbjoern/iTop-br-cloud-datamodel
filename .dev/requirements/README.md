# Requirements

- The Cloud base module extends iTop 3.3 Virtualization and Container Management. Clouds, LANs and NAT gateways carry the provider's own resource identifiers.
- The optional TeemIP bridge links a Cloud to existing IP blocks and subnets when the Cloud base and TeemIP IP Management are selected.

## NAT gateway public addresses and LAN attachments

One gateway in Cloud A has the reserved public addresses `203.0.113.10` and
`203.0.113.11`. Its provider reports two private LAN attachments:

| Cloud LAN | Gateway addresses on this LAN  |
| --------- | ------------------------------ |
| LAN 7     | `10.20.0.1/24`, `10.21.0.1/25` |
| LAN 9     | `10.30.0.1/26`                 |

The example requires two attachments, three private address assignments and two
public addresses belonging directly to the gateway. Resolve LAN 7 and LAN 9
within Cloud A. A LAN with the same provider ID in Cloud B is a different LAN.

### LAN attachments and address assignments

- `CloudNatGatewayLan` represents one attachment. Its required references are
  `nat_gateway_id` and `cloud_lan_id`. The pair is unique. The LAN must belong
  to the same Cloud as the gateway. An attachment can exist before its addresses
  are known.
- `CloudNatGatewayLanAddress` represents one host address on one attachment.
  Use `attachment_id` and the canonical host address (`address`) as its key.
  Store `prefix_length` separately. Changing a prefix updates the assignment
  and preserves its identity. A private address can recur on another LAN.
- Keep these records under the gateway in the UI. The attachment list shows
  the LAN; its address list shows each host address and prefix.
- `CloudNatGatewayPublicAddress` records each public host directly on its
  gateway. The key is `nat_gateway_id` and the canonical `address`. The
  gateway displays the assignments in `public_addresses_list`. Public
  assignments use the optional TeemIP reference in the same way as the
  private addresses.
- Remove child assignments explicitly before removing their attachment or
  gateway. This allows an importer to preserve children entered by an operator.

### With and without TeemIP

The base model keeps the host as text for IPv4 and IPv6 and the prefix length
as an integer. iTop 3.3 validates its native `AttributeIPAddress` as IPv4.
Importers should validate and normalize the host before reconciliation and
check the prefix range (0 to 32 for IPv4 or 0 to 128 for IPv6). A missing
provider prefix is unknown; a reported zero is a valid prefix. In `br-ionos-cloud-collector`,
read each CIDR from `lans[].gatewayIps[]` together with its own LAN ID.

The optional bridge adds a nullable `ipaddress_id` reference to an existing
TeemIP `IPAddress` on each assignment. Match the host in the owning
organization and its known network context. If the match is missing or
ambiguous, keep the observed address and prefix and leave the reference empty
for manual assignment. Preserve manual references during import. The prefix
describes the gateway configuration independently of the TeemIP subnet mask.

The native `IPInterface` has one address field. Its TeemIP adapter supplies a
list through `lnkIPInterfaceToIPAddress`. That link has no prefix field, and
using it as the only assignment record would require TeemIP. The two base
classes keep the attachment and its reported addresses available in both
installation profiles.
