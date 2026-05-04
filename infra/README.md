# Infrastructure

Provisions a single AWS Lightsail VM that runs the Orderbook stack via Docker Compose, fronted by Caddy with automatic TLS.

## What gets created

- One `aws_lightsail_instance` (Ubuntu 24.04, defaults to `micro_3_0` = 1GB RAM, $5/month)
- One `aws_lightsail_static_ip` attached to the instance
- Public ports 22, 80, 443 open in the Lightsail firewall

State is local (`terraform.tfstate` in this directory). Nothing else in your AWS account is touched.

## Prerequisites

- AWS credentials available (`~/.aws/credentials` or `AWS_PROFILE`)
- Terraform >= 1.5
- A DNS provider where you can add an A record (e.g. Cloudflare)

## Deploy

```bash
cd infra
cp terraform.tfvars.example terraform.tfvars
# edit terraform.tfvars: set repo_url and domain

terraform init
terraform plan        # review: should be all "+ create"
terraform apply
```

After apply, note the outputs:

- `public_ip` — the static IP
- `dns_record` — exact A record to add
- `app_url` — final URL once DNS resolves

## DNS

Add an A record at your DNS provider:

- **Name:** the subdomain part of `domain` (e.g. `orderbook` for `orderbook.example.com`)
- **Content:** the `public_ip` value
- **Proxy:** DNS only (gray cloud in Cloudflare). Caddy needs direct port 80 access for ACME, so keep Cloudflare proxy off.
- **TTL:** Auto

## What happens on first boot

cloud-init runs `bootstrap.sh` once. Takes ~5–10 minutes:

1. Installs Docker
2. Clones the repo to `/opt/orderbook`
3. Generates random `DB_PASSWORD` and `REVERB_APP_SECRET`
4. Writes prod `.env` files
5. Runs migrations + `DemoSeeder` (creates `alice@example.com` and `bob@example.com`, both password `secret-password`)
6. Builds the SPA
7. Brings the stack up

Caddy starts trying to issue a TLS cert as soon as it can reach Let's Encrypt. As long as your DNS A record is in place, the cert appears within a minute.

## Verify

```bash
# From your laptop
curl -I https://<your domain>

# SSH in (download the default keypair from Lightsail console once)
ssh -i ~/Downloads/LightsailDefaultKey-us-east-1.pem ubuntu@<public_ip>

# On the box
tail -f /var/log/cloud-init-output.log         # bootstrap progress
cd /opt/orderbook
docker compose -f docker-compose.yml -f docker-compose.prod.yml ps
docker compose -f docker-compose.yml -f docker-compose.prod.yml logs caddy --tail 50
```

## Manual reseed

```bash
cd /opt/orderbook
docker compose -f docker-compose.yml -f docker-compose.prod.yml run --rm app \
  php artisan db:seed --class=DemoSeeder --force
```

## Tear down

```bash
terraform destroy
```

Removes the instance and the static IP. Costs stop immediately.
