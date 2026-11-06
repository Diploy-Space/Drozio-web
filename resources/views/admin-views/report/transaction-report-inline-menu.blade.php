<div class="inline-page-menu my-4">
    <ul class="nav nav-pills nav--tab gap-3">
        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/report/*/transaction/order-transaction-list') ? 'active' : '' }}" href="{{ route(reportModuleRoute('transaction.order-transaction-list')) }}">
                {{ translate('Order Transactions') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/report/*/transaction/expense-transaction-list') ? 'active' : '' }}" href="{{ route(reportModuleRoute('transaction.expense-transaction-list')) }}">
                {{ translate('Expense Transactions') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/report/*/transaction/refund-transaction-list') ? 'active' : '' }}" href="{{ route(reportModuleRoute('transaction.refund-transaction-list')) }}">
                {{ translate('Refund Transactions') }}
            </a>
        </li>
    </ul>
</div>
