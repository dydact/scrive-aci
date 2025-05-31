# DNS Setup for ACI

## AWS Route 53 Setup:
```bash
PUBLIC_IP=$(curl -s ifconfig.me)
ZONE_ID=$(aws route53 list-hosted-zones --query "HostedZones[?Name=='dydact.io.'].Id" --output text | sed 's|/hostedzone/||')
aws route53 change-resource-record-sets --hosted-zone-id $ZONE_ID --change-batch '{"Changes":[{"Action":"UPSERT","ResourceRecordSet":{"Name":"aci.dydact.io","Type":"A","TTL":300,"ResourceRecords":[{"Value":"'$PUBLIC_IP'"}]}}]}'
```
