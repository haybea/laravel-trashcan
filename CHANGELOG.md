# Changelog

## [1.5.0] - 2026-08-19

### Fixed
- `export()` was missing the per-model `view` permission check every other action had.
- Gate name casing mismatch between config default and the documented `viewTrashcan` gate.
- `getAffectedChildRecords()` no longer restores-then-redeletes rows just to preview relation counts (removed spurious model events / transient un-trashing).
- Cascade-restore and the affected-children warning now resolve relations through the same helper, so they always agree.
- `autoDetectRelations()` no longer invokes arbitrary model methods (was calling `restore()`/`delete()` etc. via reflection fallback).
- Fixed a route-ordering bug that made `DELETE /model/{model}/empty` unreachable (shadowed by the `/{id}` wildcard).

### Added
- Opt-in `block_delete_with_children` config to block force-delete/bulk-force-delete while related records exist.
- Full test suite (Testbench-based unit + feature tests) and GitHub Actions CI (PHP/Laravel version matrix + Pint lint job).
- Built out the `statistics` dashboard view (was a placeholder stub).
- Cached model discovery (with live trashed-counts kept outside the cache).
- Subresource Integrity hashes on pinned CDN assets.
- Documented `guard`, `user_model`, `user_name_attribute` config keys.

### Removed
- Dead code left over from the reverted "orphaned children" feature.

## [1.0.0] - 2025-01-06

### Added
- Initial release
- Auto-discovery of soft-delete models
- Bootstrap 5 and Tailwind CSS themes
- Dark mode (toggle, auto, light, dark)
- Statistics dashboard with charts
- Activity logging
- Search, filter, and sort
- Bulk restore and delete
- Export to CSV/JSON
- Per-model permissions