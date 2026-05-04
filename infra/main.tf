terraform {
  required_version = ">= 1.5"

  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
  }
}

provider "aws" {
  region = var.region
}

locals {
  bootstrap_script = templatefile("${path.module}/bootstrap.sh", {
    repo_url = var.repo_url
    domain   = var.domain
  })
}

resource "aws_lightsail_instance" "orderbook" {
  name              = var.instance_name
  availability_zone = "${var.region}a"
  blueprint_id      = "ubuntu_24_04"
  bundle_id         = var.bundle_id

  user_data = templatefile("${path.module}/cloud-init.yaml", {
    bootstrap_b64 = base64encode(local.bootstrap_script)
  })

  tags = {
    Project = "orderbook"
  }
}

resource "aws_lightsail_static_ip" "orderbook" {
  name = "${var.instance_name}-ip"
}

resource "aws_lightsail_static_ip_attachment" "orderbook" {
  static_ip_name = aws_lightsail_static_ip.orderbook.name
  instance_name  = aws_lightsail_instance.orderbook.name
}

resource "aws_lightsail_instance_public_ports" "orderbook" {
  instance_name = aws_lightsail_instance.orderbook.name

  port_info {
    protocol  = "tcp"
    from_port = 22
    to_port   = 22
  }

  port_info {
    protocol  = "tcp"
    from_port = 80
    to_port   = 80
  }

  port_info {
    protocol  = "tcp"
    from_port = 443
    to_port   = 443
  }
}