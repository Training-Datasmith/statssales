# Architecture: statssales

## Purpose

A PrestaShop statistics module that presents total sales (order count and revenue) over a selected period, optionally broken down by payment method.

## Directory Structure

```
statssales.php   - Module class (ModuleGraph subclass); all business logic
upgrade/         - Migration scripts
tests/           - PHPUnit test stubs and PHPStan bootstrap
translations/    - Locale string overrides
```

## Key Design Decisions

- **ModuleGraph inheritance**: Renders an area/line chart of sales over the selected date range.
- **Group-by toggle**: Supports grouping by day, week, or month via the `$query_group_by` property.
- **Payment filter**: Can optionally segment results by payment module name.

## Extension Points

- Override `getData()` to add additional revenue metrics (e.g., average order value).

## Dependency Flow

```
statssales (ModuleGraph)
  └─> hookDisplayAdminStatsModules() — renders the sales chart
  └─> getData()                      — order revenue time-series query
        └─> Db::getInstance()
```
