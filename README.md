# Docker N8N Hosting Module for WHMCS

A powerful WHMCS provisioning module that automates n8n provisioning on any Docker-enabled server. This module is a superior, drop-in replacement for the PUQ Cloud N8N module, featuring smarter service detection and built-in infrastructure automation.

## ✨ Key Features
- **Zero Dependencies**: Completely independent, no external licensing server required.
- **Auto-Infrastructure**: Automatically installs and configures `nginx-proxy` and `letsencrypt-companion` if missing.
- **Smart Detection**: Advanced logic to identify and take over existing PUQ Cloud or legacy n8n containers.
- **Resource Management**: Native support for CPU, RAM, and Disk (Loopback image) limits.
- **Update Manager**: Built-in WHMCS addon for one-click updates directly from GitHub.
- **Templates**: Support for SQLite, PostgreSQL, and Queue mode architectures.

## 🚀 Quick Setup
1. Upload the `modules` folder to your WHMCS root.
2. Activate the **Docker N8N Manager** addon in WHMCS.
3. Add your Docker server in WHMCS (Requirements: Debian/Ubuntu with Docker installed).
4. Create a product and select **Docker N8N** as the module.
5. Enable **Auto-setup Infrastructure** in module settings for full automation.

## 🛠️ Infrastructure Requirements
- **OS**: Debian 12 / Ubuntu 22.04+
- **Software**: Docker & Docker Compose Plugin
- **Network**: Port 80, 443, and SSH (Default: 22) must be open.

## 🤝 Community & Support
This project is released under the **GPL-3.0 License**. Feel free to fork, contribute, or report issues on GitHub.

Created with ❤️ by [cyber-wahid](https://github.com/cyber-wahid)
