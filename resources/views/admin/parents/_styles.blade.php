<style>
    .parents-page {
        display: grid;
        gap: 18px;
    }

    .parents-hero {
        background: linear-gradient(135deg, #eff6ff 0%, #ffffff 60%, #f8fafc 100%);
        border: 1px solid #dbeafe;
        border-radius: 22px;
        padding: 22px;
    }

    .parents-header {
        display: flex;
        justify-content: space-between;
        gap: 18px;
        align-items: flex-start;
        flex-wrap: wrap;
    }

    .parents-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 12px;
        border-radius: 999px;
        background: #dbeafe;
        color: #1d4ed8;
        font-size: 13px;
        font-weight: 800;
        margin-bottom: 10px;
    }

    .parents-title h2 {
        margin: 0;
        font-size: 28px;
        color: #111827;
    }

    .parents-title p {
        margin: 8px 0 0;
        color: #667085;
    }

    .parents-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: center;
    }

    .parents-stats-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
    }

    .parents-stat-card {
        padding: 18px;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        background: #ffffff;
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.04);
    }

    .parents-stat-label {
        font-size: 13px;
        color: #667085;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: 8px;
    }

    .parents-stat-value {
        font-size: 32px;
        font-weight: 900;
        color: #111827;
        line-height: 1;
    }

    .parents-stat-help {
        margin-top: 6px;
        color: #667085;
        font-size: 13px;
    }

    .parents-filter-form {
        display: grid;
        grid-template-columns: minmax(260px, 1fr) 190px 190px 190px auto auto;
        gap: 12px;
        align-items: end;
    }

    .parents-field label {
        display: block;
        margin-bottom: 6px;
        font-weight: 700;
        font-size: 13px;
        color: #344054;
    }

    .parents-field input,
    .parents-field select,
    .parents-field textarea {
        width: 100%;
        border: 1px solid #d0d5dd;
        border-radius: 12px;
        padding: 10px 12px;
        min-height: 42px;
        background: #ffffff;
    }

    .parents-field textarea {
        min-height: 110px;
        resize: vertical;
    }

    .parents-table-wrap {
        overflow-x: auto;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        background: #ffffff;
    }

    .parents-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        min-width: 1100px;
        background: #ffffff;
    }

    .parents-table thead th {
        background: #f9fafb;
        color: #475467;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .04em;
        text-align: left;
        padding: 14px;
        border-bottom: 1px solid #e5e7eb;
        white-space: nowrap;
    }

    .parents-table tbody td {
        padding: 14px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }

    .parents-table tbody tr:hover {
        background: #fcfcfd;
    }

    .parents-table tbody tr:last-child td {
        border-bottom: none;
    }

    .parent-person {
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .parent-avatar {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        background: #eff6ff;
        color: #1d4ed8;
        display: grid;
        place-items: center;
        font-weight: 900;
        border: 1px solid #bfdbfe;
        flex: 0 0 auto;
    }

    .parent-name {
        font-weight: 900;
        color: #111827;
    }

    .muted {
        color: #667085;
        font-size: 12px;
    }

    .parents-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 800;
        white-space: nowrap;
    }

    .badge-blue {
        background: #eff6ff;
        color: #1d4ed8;
    }

    .badge-green {
        background: #ecfdf3;
        color: #047857;
    }

    .badge-yellow {
        background: #fffbeb;
        color: #b45309;
    }

    .badge-gray {
        background: #f3f4f6;
        color: #4b5563;
    }

    .badge-red {
        background: #fef2f2;
        color: #b91c1c;
    }

    .parents-action-row {
        display: flex;
        gap: 8px;
        justify-content: flex-end;
        align-items: center;
        flex-wrap: wrap;
    }

    .parents-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    .parents-form-section {
        padding: 18px;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        background: #ffffff;
    }

    .parents-form-section h3 {
        margin: 0 0 12px;
        color: #111827;
    }

    .parents-error {
        color: #b91c1c;
        font-size: 12px;
        margin-top: 5px;
    }

    .parents-switch-row {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: center;
        padding: 12px;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: #f9fafb;
    }

    .parents-switch-row input {
        width: 20px;
        height: 20px;
    }

    .parents-detail-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    .parents-info-list {
        display: grid;
        gap: 10px;
    }

    .parents-info-item {
        display: grid;
        grid-template-columns: 220px 1fr;
        gap: 12px;
        padding: 12px;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: #ffffff;
    }

    .parents-info-item span {
        color: #667085;
        font-size: 13px;
    }

    .parents-info-item strong {
        color: #111827;
    }

    .parents-score-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
    }

    .parents-score-box {
        padding: 14px;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        background: #ffffff;
    }

    .parents-score-value {
        font-size: 26px;
        font-weight: 900;
        color: #111827;
    }

    .parents-alert-success,
    .parents-alert-error {
        padding: 14px 16px;
        border-radius: 16px;
        font-weight: 700;
    }

    .parents-alert-success {
        background: #ecfdf3;
        color: #047857;
        border: 1px solid #a7f3d0;
    }

    .parents-alert-error {
        background: #fef2f2;
        color: #b91c1c;
        border: 1px solid #fecaca;
    }

    .parents-pagination {
        margin-top: 18px;
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: center;
        flex-wrap: wrap;
        padding: 14px;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        background: #ffffff;
    }

    .parents-pagination-info {
        color: #667085;
        font-size: 13px;
    }

    .parents-pagination-links {
        display: flex;
        gap: 6px;
        align-items: center;
        flex-wrap: wrap;
    }

    .parents-page-link,
    .parents-page-disabled,
    .parents-page-active {
        min-width: 38px;
        height: 38px;
        padding: 0 12px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        font-weight: 800;
        text-decoration: none;
        border: 1px solid #e5e7eb;
    }

    .parents-page-link {
        background: #ffffff;
        color: #344054;
    }

    .parents-page-link:hover {
        background: #eff6ff;
        border-color: #bfdbfe;
        color: #1d4ed8;
    }

    .parents-page-active {
        background: #2563eb;
        color: #ffffff;
        border-color: #2563eb;
    }

    .parents-page-disabled {
        background: #f9fafb;
        color: #9ca3af;
        cursor: not-allowed;
    }

    .empty-state {
        text-align: center;
        padding: 38px 16px;
        color: #667085;
    }

    @media (max-width: 1200px) {
        .parents-filter-form {
            grid-template-columns: 1fr 1fr;
        }

        .parents-stats-grid,
        .parents-detail-grid,
        .parents-form-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 720px) {
        .parents-title h2 {
            font-size: 24px;
        }

        .parents-filter-form {
            grid-template-columns: 1fr;
        }

        .parents-actions,
        .parents-actions .btn,
        .parents-actions a,
        .parents-actions button,
        .parents-actions form {
            width: 100%;
        }

        .parents-info-item {
            grid-template-columns: 1fr;
        }

        .parents-score-grid {
            grid-template-columns: 1fr;
        }

        .parents-pagination-links {
            width: 100%;
        }

        .parents-page-link,
        .parents-page-disabled,
        .parents-page-active {
            flex: 1;
        }
    }
</style>