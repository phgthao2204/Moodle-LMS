# TMS Bootstrap

Idempotent Liferay bootstrap bundle for DevLocal. On deployment it creates the
public `/home` widget page inside the existing `/tms` site when that page is
missing. It uses Liferay local services instead of the JSONWS endpoint removed
from Liferay Portal 7.4 GA132.
