# System Architecture

## Overview

NoniKos is a web-based property and boarding house management application built with Laravel. It provides a centralized platform for managing properties, rooms, tenants, rental payments, financial records, and daily operations.

## Technology Stack

- **Backend:** PHP and Laravel
- **Frontend:** Blade, JavaScript, and Tailwind CSS
- **Database:** MySQL / MariaDB
- **Build Tool:** Vite
- **Testing:** PHPUnit

## Application Structure

The application follows Laravel's MVC architecture.

- **Models:** Manage database entities and relationships.
- **Controllers:** Handle application requests and business workflows.
- **Views:** Render user interfaces using Laravel Blade.
- **Middleware:** Handle authentication and access restrictions.
- **Policies and Gates:** Enforce authorization rules.
- **Services:** Encapsulate reusable application logic.

## Main Modules

- Property and room management.
- Tenant and staff management.
- Rental payments and arrears.
- Invoice and financial management.
- Reports and data exports.
- Activity logging.
- Backup and restore.
- Owner and tenant portals.

## Access Control

NoniKos uses role-based access control to separate responsibilities and restrict access to authorized features.

## Background Processing

Laravel scheduling is used for recurring application tasks, including operational reminders and scheduled maintenance processes.

## Security

The application incorporates authentication, authorization, request validation, and access restrictions to protect application resources.

For more details, see [Security Documentation](SECURITY.md).
