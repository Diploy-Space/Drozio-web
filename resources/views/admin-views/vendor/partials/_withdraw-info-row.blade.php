<li class="d-flex flex-wrap align-items-start justify-content-between gap-2 py-2">
    <span class="fs-12 text-muted">{{ $label }}</span>
    <span class="fs-14 fw-medium text-dark text-break text-end">{{ filled($value) ? $value : translate('N/A') }}</span>
</li>
