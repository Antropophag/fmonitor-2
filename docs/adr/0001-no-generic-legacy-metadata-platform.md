---
status: accepted
---

# Do not carry the generic legacy metadata platforms into FMonitor 2.0

FMonitor 2.0 does not import or execute the generic MDM platform (`mdm_user_table_*` plus its metadata and rights) or the generic custom-field/view-builder platform (`fm_fields`, `fm_fields_values`, `fm_views*`, runtime formulas and dynamic columns) as runtime schema or capability. Those platforms were legacy mechanisms for uncertainty about required fields and presentations; FMonitor 2.0 instead uses explicit domain contracts, screens and tables. Read-only legacy metadata may be consulted transiently to interpret selected physical `fm_maintable` columns or evidence during migration, but the metadata, formulas and views themselves are not imported or executed. Dynamic MDM content remains outside migration scope unless the product owner later identifies a specific domain dataset through a separate SSD decision.
