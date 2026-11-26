<?php

use App\Http\Controllers\Erp\ActivityController;
use App\Http\Controllers\Erp\AttachmentController;
use App\Http\Controllers\Erp\CategoryController;
use App\Http\Controllers\Erp\CompanyProfileController;
use App\Http\Controllers\Erp\CustomerController;
use App\Http\Controllers\Erp\CustomerPaymentController;
use App\Http\Controllers\Erp\DashboardController;
use App\Http\Controllers\Erp\DraftInvoiceController;
use App\Http\Controllers\Erp\EtaController;
use App\Http\Controllers\Erp\ExpenseController;
use App\Http\Controllers\Erp\ExportController;
use App\Http\Controllers\Erp\InventoryController;
use App\Http\Controllers\Erp\NotificationController;
use App\Http\Controllers\Erp\PreparationOrderController;
use App\Http\Controllers\Erp\ProductAliasController;
use App\Http\Controllers\Erp\ProductController;
use App\Http\Controllers\Erp\PurchaseInvoiceController;
use App\Http\Controllers\Erp\ReportController;
use App\Http\Controllers\Erp\ReturnController;
use App\Http\Controllers\Erp\SaleInvoiceController;
use App\Http\Controllers\Erp\SalesRepController;
use App\Http\Controllers\Erp\StockAdjustmentController;
use App\Http\Controllers\Erp\StockCountController;
use App\Http\Controllers\Erp\SupplierController;
use App\Http\Controllers\Erp\TreasuryController;
use App\Http\Controllers\Erp\UserController;
use App\Http\Controllers\Erp\WarehouseController;
use App\Http\Controllers\Erp\WhatsAppShareController;
use Illuminate\Support\Facades\Route;

/*
 * مسارات النظام الأساسية — مش متداخلة مع {current_team} عشان الروابط تفضل
 * نضيفة (/sales بدل /team-slug/sales).
 */
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('erp.dashboard');

    // التجار
    Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::post('customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::put('customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
    Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::get('customers/{customer}/statement', [CustomerController::class, 'statement'])->name('customers.statement');
    Route::post('customers/{customer}/payments', [CustomerPaymentController::class, 'store'])->name('customers.payments.store');

    /*
     * الفواتير الممسوحة — مسودات لحد ما المستخدم يراجع ويأكّد.
     * قبل `sales/{sale}` عشان «drafts» ما تتقراش كرقم فاتورة.
     */
    Route::get('sales/drafts/{draft}', [DraftInvoiceController::class, 'showSale'])->name('sales.drafts.show');
    Route::put('sales/drafts/{draft}', [DraftInvoiceController::class, 'updateSale'])->name('sales.drafts.update');
    Route::post('sales/drafts/{draft}/confirm', [DraftInvoiceController::class, 'confirmSale'])->name('sales.drafts.confirm');
    Route::get('purchases/drafts/{draft}', [DraftInvoiceController::class, 'showPurchase'])->name('purchases.drafts.show');
    Route::put('purchases/drafts/{draft}', [DraftInvoiceController::class, 'updatePurchase'])->name('purchases.drafts.update');
    Route::post('purchases/drafts/{draft}/confirm', [DraftInvoiceController::class, 'confirmPurchase'])->name('purchases.drafts.confirm');
    Route::post('drafts/{draft}/switch-type', [DraftInvoiceController::class, 'switchType'])->name('drafts.switch-type');
    Route::delete('drafts/{draft}', [DraftInvoiceController::class, 'destroy'])->name('drafts.destroy');

    // المبيعات
    Route::get('sales', [SaleInvoiceController::class, 'index'])->name('sales.index');
    Route::get('sales/create', [SaleInvoiceController::class, 'create'])->name('sales.create');
    Route::post('sales', [SaleInvoiceController::class, 'store'])->name('sales.store');
    Route::get('sales/{sale}', [SaleInvoiceController::class, 'show'])->name('sales.show');
    Route::get('sales/{sale}/print', [SaleInvoiceController::class, 'print'])->name('sales.print');
    Route::post('sales/{sale}/eta', [EtaController::class, 'store'])->name('sales.eta.store');

    // المشتريات
    Route::get('purchases', [PurchaseInvoiceController::class, 'index'])->name('purchases.index');
    Route::get('purchases/create', [PurchaseInvoiceController::class, 'create'])->name('purchases.create');
    Route::post('purchases', [PurchaseInvoiceController::class, 'store'])->name('purchases.store');
    Route::get('purchases/{purchase}', [PurchaseInvoiceController::class, 'show'])->name('purchases.show');

    /*
     * طلبات التحضير — ورقة بتروح للمخزن، مفيش أسعار فيها.
     * قبل `{preparationOrder}` عشان «create» ما تتقراش كرقم طلب.
     */
    Route::get('preparation-orders', [PreparationOrderController::class, 'index'])->name('preparation-orders.index');
    Route::get('preparation-orders/create', [PreparationOrderController::class, 'create'])->name('preparation-orders.create');
    Route::post('preparation-orders', [PreparationOrderController::class, 'store'])->name('preparation-orders.store');
    Route::get('preparation-orders/{preparationOrder}', [PreparationOrderController::class, 'show'])->name('preparation-orders.show');
    Route::get('preparation-orders/{preparationOrder}/print', [PreparationOrderController::class, 'print'])->name('preparation-orders.print');
    Route::get('preparation-orders/{preparationOrder}/convert', [PreparationOrderController::class, 'convert'])->name('preparation-orders.convert');
    Route::post('preparation-orders/{preparationOrder}/prepare', [PreparationOrderController::class, 'prepare'])->name('preparation-orders.prepare');
    Route::post('preparation-orders/{preparationOrder}/cancel', [PreparationOrderController::class, 'cancel'])->name('preparation-orders.cancel');

    /*
     * المرتجعات — «create» و«orphan» قبل `{return}` عشان ما يتقروش
     * كأرقام مستندات.
     */
    Route::get('returns', [ReturnController::class, 'index'])->name('returns.index');
    Route::get('returns/orphan', [ReturnController::class, 'createOrphan'])->name('returns.orphan.create');
    Route::post('returns/orphan', [ReturnController::class, 'storeOrphan'])->name('returns.orphan.store');
    Route::get('invoices/{invoice}/return', [ReturnController::class, 'create'])->name('returns.create');
    Route::post('invoices/{invoice}/return', [ReturnController::class, 'store'])->name('returns.store');
    Route::get('returns/{return}', [ReturnController::class, 'show'])->name('returns.show');
    Route::get('returns/{return}/print', [ReturnController::class, 'print'])->name('returns.print');
    Route::post('returns/{return}/eta', [ReturnController::class, 'sendToEta'])->name('returns.eta.store');

    // الموردون
    Route::get('suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
    Route::post('suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
    Route::put('suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
    Route::delete('suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');
    Route::get('suppliers/{supplier}', [SupplierController::class, 'show'])->name('suppliers.show');
    Route::post('suppliers/{supplier}/payments', [SupplierController::class, 'storePayment'])->name('suppliers.payments.store');

    // التنبيهات — الجرس وإعداداته
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::get('settings/notifications', [NotificationController::class, 'settings'])->name('notifications.settings');
    Route::put('settings/notifications', [NotificationController::class, 'updateSettings'])->name('notifications.settings.update');
    Route::put('settings/whatsapp-target', [NotificationController::class, 'updateWhatsAppTarget'])
        ->name('notifications.whatsapp-target');
    /*
     * إيميلات التنبيهات — عناوين بتستقبل تنبيهات المخزون من غير ما
     * تكون مستخدمين. للمدير بس.
     */
    Route::post('settings/alert-emails', [NotificationController::class, 'storeAlertEmail'])->name('alert-emails.store');
    Route::put('settings/alert-emails/{alertEmail}', [NotificationController::class, 'updateAlertEmail'])->name('alert-emails.update');
    Route::delete('settings/alert-emails/{alertEmail}', [NotificationController::class, 'destroyAlertEmail'])->name('alert-emails.destroy');
    Route::post('settings/alert-emails/test', [NotificationController::class, 'sendTestEmail'])
        // إرسال حقيقي — سقف عشان الزرار ما يتحوّلش لمرسال spam
        ->middleware('throttle:6,1')
        ->name('alert-emails.test');

    Route::post('notifications/push', [NotificationController::class, 'subscribePush'])->name('notifications.push.subscribe');
    Route::delete('notifications/push', [NotificationController::class, 'unsubscribePush'])->name('notifications.push.unsubscribe');

    /*
     * أذون المخزن والجرد.
     *
     * الكتابة لأي حد عنده الصلاحية، والاعتماد للمدير والمحاسب —
     * والاعتماد هو اللي بيحرّك البضاعة.
     */
    Route::get('stock-adjustments', [StockAdjustmentController::class, 'index'])->name('stock-adjustments.index');
    Route::get('stock-adjustments/create', [StockAdjustmentController::class, 'create'])->name('stock-adjustments.create');
    Route::post('stock-adjustments', [StockAdjustmentController::class, 'store'])->name('stock-adjustments.store');
    Route::get('stock-adjustments/{stockAdjustment}', [StockAdjustmentController::class, 'show'])->name('stock-adjustments.show');
    Route::get('stock-adjustments/{stockAdjustment}/print', [StockAdjustmentController::class, 'print'])->name('stock-adjustments.print');
    Route::post('stock-adjustments/{stockAdjustment}/approve', [StockAdjustmentController::class, 'approve'])->name('stock-adjustments.approve');

    Route::get('stock-counts', [StockCountController::class, 'index'])->name('stock-counts.index');
    Route::get('stock-counts/create', [StockCountController::class, 'create'])->name('stock-counts.create');
    Route::post('stock-counts', [StockCountController::class, 'store'])->name('stock-counts.store');
    Route::get('stock-counts/{stockCount}', [StockCountController::class, 'show'])->name('stock-counts.show');
    Route::get('stock-counts/{stockCount}/print', [StockCountController::class, 'print'])->name('stock-counts.print');
    Route::put('stock-counts/{stockCount}', [StockCountController::class, 'saveCounted'])->name('stock-counts.save');
    Route::post('stock-counts/{stockCount}/approve', [StockCountController::class, 'approve'])->name('stock-counts.approve');
    Route::post('stock-counts/{stockCount}/cancel', [StockCountController::class, 'cancel'])->name('stock-counts.cancel');

    // المخزون
    Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('inventory/{product}', [InventoryController::class, 'show'])->name('inventory.show');

    // الأسماء البديلة للصنف — اللي النظام بيتعلّمها من الفواتير الممسوحة
    Route::post('products/{product}/aliases', [ProductAliasController::class, 'store'])->name('products.aliases.store');
    Route::delete('product-aliases/{alias}', [ProductAliasController::class, 'destroy'])->name('products.aliases.destroy');

    // الأصناف — كتالوج البضاعة نفسه (الأسعار والوحدات وحد الطلب)
    Route::get('products', [ProductController::class, 'index'])->name('products.index');
    Route::post('products', [ProductController::class, 'store'])->name('products.store');
    // إضافة سريعة من جوه فورم الفاتورة — صلاحية أوسع شوية من الكتالوج
    Route::post('products/quick', [ProductController::class, 'quickStore'])->name('products.quick-store');
    Route::put('products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

    // التصنيفات
    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::put('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    // المخازن — أول حاجة بتتسجّل في نظام فاضي
    Route::get('warehouses', [WarehouseController::class, 'index'])->name('warehouses.index');
    Route::post('warehouses', [WarehouseController::class, 'store'])->name('warehouses.store');
    Route::put('warehouses/{warehouse}', [WarehouseController::class, 'update'])->name('warehouses.update');
    Route::delete('warehouses/{warehouse}', [WarehouseController::class, 'destroy'])->name('warehouses.destroy');

    // المرفقات (صور وPDF على الفواتير والسندات)
    Route::post('invoices/{invoice}/attachments', [AttachmentController::class, 'storeForInvoice'])->name('invoices.attachments.store');
    Route::post('payments/{payment}/attachments', [AttachmentController::class, 'storeForPayment'])->name('payments.attachments.store');
    Route::get('attachments/{attachment}', [AttachmentController::class, 'show'])->name('attachments.show');
    Route::get('attachments/{attachment}/download', [AttachmentController::class, 'download'])->name('attachments.download');
    Route::delete('attachments/{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');

    // المندوبين
    Route::get('reps', [SalesRepController::class, 'index'])->name('reps.index');
    Route::post('reps', [SalesRepController::class, 'store'])->name('reps.store');
    Route::put('reps/{rep}', [SalesRepController::class, 'update'])->name('reps.update');
    Route::delete('reps/{rep}', [SalesRepController::class, 'destroy'])->name('reps.destroy');
    Route::get('reps/{rep}', [SalesRepController::class, 'show'])->name('reps.show');

    // المستخدمين والأدوار — المدير لوحده
    Route::get('activity', [ActivityController::class, 'index'])->name('activity.index');
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    /*
     * بيانات الشركة — الترويسة على كل مستند.
     * POST مش PUT لأن اللوجو بيترفع multipart.
     */
    /*
     * مشاركة الواتساب.
     *
     * فتح شات `wa.me` كله في المتصفح ومش محتاج راوت. الاتنين دول
     * للحاجتين اللي لازم يعدّوا على السيرفر: الإرسال المباشر بالـ
     * Cloud API، وحفظ رقم التاجر لما يكون ناقص.
     */
    Route::post('whatsapp/send', [WhatsAppShareController::class, 'send'])
        ->middleware('throttle:20,1')
        ->name('whatsapp.share');
    Route::post('whatsapp/phone', [WhatsAppShareController::class, 'storePhone'])
        ->name('whatsapp.phone');

    Route::get('settings/company', [CompanyProfileController::class, 'edit'])->name('company.edit');
    Route::post('settings/company', [CompanyProfileController::class, 'update'])->name('company.update');

    /*
     * المصروفات وشجرة تصنيفاتها.
     * «categories» قبل أي باراميتر عشان ما تتقراش كرقم مصروف.
     */
    Route::get('expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::post('expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::get('expenses/categories', [ExpenseController::class, 'categories'])->name('expense-categories.index');
    Route::post('expenses/categories', [ExpenseController::class, 'storeCategory'])->name('expense-categories.store');
    Route::put('expenses/categories/{expenseCategory}', [ExpenseController::class, 'updateCategory'])->name('expense-categories.update');
    Route::delete('expenses/categories/{expenseCategory}', [ExpenseController::class, 'destroyCategory'])->name('expense-categories.destroy');

    // الخزينة
    Route::get('treasury', [TreasuryController::class, 'index'])->name('treasury.index');

    // التقارير
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/print', [ReportController::class, 'print'])->name('reports.print');

    /*
     * التحميل — PDF أو إكسيل. صفحات الطباعة فاضلة زي ما هي:
     * الطباعة للورق، والتحميل للإرسال والأرشفة.
     */
    Route::get('exports/report', [ExportController::class, 'report'])->name('exports.report');
    // قوايم الفواتير — بتتحمّل بفلاتر الشاشة الحالية
    Route::get('exports/sales', [ExportController::class, 'saleList'])->name('exports.sales');
    Route::get('exports/purchases', [ExportController::class, 'purchaseList'])->name('exports.purchases');
    Route::get('exports/returns', [ExportController::class, 'returnList'])->name('exports.returns');
    Route::get('exports/customers/{customer}', [ExportController::class, 'customerStatement'])->name('exports.customer');
    Route::get('exports/suppliers/{supplier}', [ExportController::class, 'supplierStatement'])->name('exports.supplier');
    Route::get('exports/invoices/{invoice}', [ExportController::class, 'invoice'])->name('exports.invoice');
});

/*
 * PDF طلب التحضير — بتوقيع، من غير تسجيل دخول.
 *
 * ده اللي بيخلّي مشاركة الواتساب تشتغل من غير أي API: الصفحة بتفتح
 * `wa.me` برسالة فيها اللينك ده، والسواق أو التاجر بيفتحه من موبايله
 * على طول. التوقيع هو اللي بيحميه — اللينك بيقع لوحده بعد أسبوع، وأي
 * تغيير في حرف واحد فيه بيبطّله.
 *
 * ومفيش أسعار على الورقة أصلاً، فأسوأ حاجة ممكن تتكشف هي كميات طلب
 * واحد.
 */
Route::middleware('signed')
    ->get('preparation-orders/{preparationOrder}/pdf', [PreparationOrderController::class, 'pdf'])
    ->name('preparation-orders.pdf');

/*
 * PDF الفاتورة — بتوقيع كمان، عشان تتبعت للتاجر على الواتساب.
 *
 * الفرق عن طلب التحضير إن **فيها أسعار**: اللينك ده بيكشف فاتورة
 * واحدة لأي حد يوصله. وده الغرض منه — بيروح لصاحبها — بس الصلاحية
 * أسبوع مش مفتوحة، والمسودّة مابتتفتحش أصلاً.
 */
Route::middleware('signed')
    ->get('invoices/{invoice}/pdf', [ExportController::class, 'signedInvoice'])
    ->name('invoices.pdf');
