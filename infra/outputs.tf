output "public_ip" {
  description = "Static public IP attached to the instance."
  value       = aws_lightsail_static_ip.orderbook.ip_address
}

output "ssh" {
  description = "SSH command using the Lightsail default keypair downloaded from the AWS console."
  value       = "ssh ubuntu@${aws_lightsail_static_ip.orderbook.ip_address}"
}

output "dns_record" {
  description = "DNS record to add manually in your DNS provider."
  value       = "A  ${var.domain}  ->  ${aws_lightsail_static_ip.orderbook.ip_address}  (DNS only, TTL auto)"
}

output "app_url" {
  description = "Public URL once DNS propagates and Caddy issues the TLS cert."
  value       = "https://${var.domain}"
}