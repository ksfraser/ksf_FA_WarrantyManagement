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

## Development Workflow

All development is done in the **devel tree** (`~/Documents/ksf_FA_WarrantyManagement`). Do **not** edit files in the UAT bind point directly.

### Workflow Steps
1. **Develop** in this repo (feature branches preferred)
2. **Test**: run repo-appropriate tests
3. **Lint**: `php -l` on modified PHP files (no syntax errors)
4. **Commit** and **Push** branch to GitHub
5. **Merge** to `master` when ready
6. **Push** `master` to GitHub
7. **Deploy** to UAT by pulling in the Infrastructure bind point:

   ```
   cd ~/ksf_Infrastructure/fa_modules/ksf_FA_WarrantyManagement
   git stash -u
   git pull origin master
   git stash pop
   ```

### UAT Bind Point
| Path | Purpose |
|------|---------|
| `~/Documents/ksf_FA_WarrantyManagement` | Devel tree — all development, testing, commits |
| `~/ksf_Infrastructure/fa_modules/ksf_FA_WarrantyManagement` | UAT bind point — deployment target, integration testing (if mirrored) |

