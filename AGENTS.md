# AGENTS.md - ksf_FA_WarrantyManagement#

## Architecture Overview#

**FA Module** for Warranty Management - product warranties, claims, and service integration.

### Core Principles#
- **SOLID**, **DRY**, **TDD**, **DI**, **SRP**#

## Repository Structure#

```
ksf_FA_WarrantyManagement/
├── sql/#
│   ├── fa_warranties.sql#
│   ├── fa_warranty_claims.sql#
│   └── fa_warranty_terms.sql#
├── includes/#
│   ├── warranties_db.inc#
│   ├── claims_db.inc#
│   └── terms_db.inc#
├── pages/#
├── hooks.php#
├── composer.json#
└── ProjectDocs/#
```

## Dependencies#

- **ksf_FA_WarrantyManagement_Core** (business logic)#
- **ksf_FA_Service** (link to service orders)#
- **FrontAccounting 2.4+**#
