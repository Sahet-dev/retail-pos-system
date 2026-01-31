# Retail POS & Inventory Core

An event-driven retail POS and inventory management system built with Laravel and Vue 3.

This project focuses on **correct stock tracking**, **auditability**, and **realtime updates**, inspired by enterprise retail systems (SAP-style concepts) but implemented in a lightweight, modern stack.

---

## Features

- Barcode-based product scanning
- Open / close sale lifecycle
- Event-based stock movements (sale, inbound, loss)
- Stock per location (store / warehouse)
- Realtime updates via WebSockets (Laravel Reverb)
- Clean audit trail for all inventory changes

---

## Core Concepts

This system follows a few strict rules:

- Products are identities, not quantities
- Stock exists per location
- Sales and losses are **events**, not silent updates
- Inventory changes are always logged
- State is derived from history

This makes the system:
- auditable
- debuggable
- scalable

---

## Tech Stack

**Backend**
- Laravel 12
- MySQL / PostgreSQL
- Laravel Reverb (WebSockets)

**Frontend**
- Vue 3 (`<script setup>`)
- Vite

**Other**
- Redis (queues / broadcasting, optional)

---

## Project Structure (Core Models)

- Product
- Location
- Stock
- StockMovement
- Sale
- SaleItem

Relationships are designed to mirror real retail operations.

---

## Local Setup

```bash
git clone git@github.com:Sahet-dev/retail-pos-core.git
cd retail-pos-core

composer install
cp .env.example .env
php artisan key:generate

php artisan migrate --seed
php artisan serve
