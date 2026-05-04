variable "region" {
  description = "AWS region for the Lightsail instance."
  type        = string
  default     = "us-east-1"
}

variable "instance_name" {
  description = "Name of the Lightsail instance and prefix for related resources."
  type        = string
  default     = "orderbook"
}

variable "bundle_id" {
  description = "Lightsail size. nano_3_0 (512MB, $3.50), micro_3_0 (1GB, $5), small_3_0 (2GB, $10)."
  type        = string
  default     = "micro_3_0"
}

variable "repo_url" {
  description = "Public Git URL the instance clones at boot."
  type        = string
}

variable "domain" {
  description = "Public hostname this instance serves. An A record for this name must point to the static IP after apply."
  type        = string
}
