# 🚀 Docker N8N Module - Complete Setup Guide

This comprehensive guide will walk you through configuring WHMCS to sell N8N instances with full automation.

---

## 📋 Server Requirements
- **OS Recommended**: Debian 12 / Ubuntu 22.04+ (LTS recommended)
- **Software**: Docker & Docker Compose Plugin (Required)
- **SSH Access**: Root or user with sudo privileges.

---

## 🛠️ Part 1: Server Side Setup

Previously, you had to manually set up networks and proxy containers. **This is now automated!**

### 1. Simple Server Preparation
Just make sure Docker is installed on your server:
```bash
curl -fsSL https://get.docker.com | sh
```
That's it! The module will handle the rest (Network creation, Nginx Proxy, and SSL Companion) automatically when you provision your first service or click "Test Connection".

---

## 📦 Part 2: WHMCS Module Installation

### 1. Upload Files
Upload the `modules` folder to your WHMCS root directory.

### 2. Activate Manager Addon
1. Go to **System Settings > Addon Modules**.
2. Find **Docker N8N Manager** and click **Activate**.
3. Click **Configure**, select the admin roles, and **Save Changes**.
4. Access it via **Addons > Docker N8N Manager** to track versions and updates.

### 3. Configure Server
1. Go to **System Settings > Servers > Add New Server**.
2. **Hostname/IP**: Your server details.
3. **Module**: Select **Docker N8N**.
4. **Username/Password**: Your SSH credentials.
5. **Access Hash**: Leave empty for Port 22, or enter `PORT:2222` for custom port.
6. Click **Test Connection**. This will also check and install required infrastructure if **Auto-setup** is enabled.

---

## 🛍️ Part 3: Creating the Product

1. Go to **System Settings > Products/Services**.
2. Create/Edit a product and select **Docker N8N** as the module.
3. In **Module Settings**:
   - **Base Domain**: Set your main domain (e.g., `n8n.yourcompany.com`).
   - **Auto-setup Infrastructure**: Check this to automate everything!
   - **Template**: Choose SQLite (Basic) or PostgreSQL (Production).

---

## ✅ Drop-in Replacement for PUQ Cloud
If you are migrating from PUQ Cloud:
1. Deactivate the PUQ module and activate **Docker N8N**.
2. Ensure the product configuration matches your existing setup.
3. The module's **Smart Detection** will automatically find and manage your existing PUQ containers based on their environment variables and naming patterns.

---

## ❓ Troubleshooting

**Q: SSL Not Working?**
A: Ensure your domain's DNS A record points to the server IP. The module handles the rest.

**Q: Update Available?**
A: Go to **Addons > Docker N8N Manager** and click "Update Now" to get the latest version from GitHub.

---
Released under **GPL-3.0**. Support via [GitHub](https://github.com/cyber-wahid/docker-n8n-hosting-module-whmcs)
