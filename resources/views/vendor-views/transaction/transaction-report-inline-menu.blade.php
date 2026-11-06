<div class="inline-page-menu my-4">
    <ul class="list-unstyled">
        <li class="{{ Request::is('vendor/report/*/transaction/order-list') ? 'active' : '' }}"><a href="{{ route(reportModuleRoute('transaction.order-list')) }}">{{ translate('Order Transactions') }}</a></li>
        <li class="{{ Request::is('vendor/report/*/transaction/expense-list') ? 'active' : '' }}"><a href="{{ route(reportModuleRoute('transaction.expense-list')) }}">{{ translate('Expense Transactions') }}</a></li>
    </ul>
</div>
