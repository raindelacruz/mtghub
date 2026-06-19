<?php

require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Card.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Listing.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Order.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'PriceHistory.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'User.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Wallet.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'WishlistItem.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Report.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'AdminAuditLog.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'OrderMessage.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Dispute.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'MarketplaceReview.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Operations.php';

class AdminController extends Controller
{
    private Card $cards;
    private Listing $listings;
    private Order $orders;
    private PriceHistory $prices;
    private User $users;
    private WishlistItem $wantedLists;
    private Report $reports;

    public function __construct()
    {
        $this->cards = new Card();
        $this->listings = new Listing();
        $this->orders = new Order();
        $this->prices = new PriceHistory();
        $this->users = new User();
        $this->wantedLists = new WishlistItem();
        $this->reports = new Report();
    }

    public function dashboard(): void
    {
        $this->requireAdmin();

        $this->view('admin.dashboard', [
            'title' => 'Admin Panel',
            'counts' => [
                'users' => $this->users->countAll(),
                'cards' => $this->cards->countAll(),
                'listings' => $this->listings->countAll(),
                'prices' => $this->prices->countAll(),
                'reviewListings' => $this->listings->countSuspiciousQueue(),
                'openReports' => $this->reports->countOpen(),
            ],
            'recentListings' => array_slice($this->listings->allForAdmin(), 0, 8),
            'recentPrices' => $this->prices->recent(8),
        ]);
    }

    public function users(): void
    {
        $this->requireAdmin();

        $this->view('admin.users', [
            'title' => 'Manage Users',
            'users' => $this->users->all(),
        ]);
    }

    public function updateUserRole(): void
    {
        $this->requireAdmin();
        $userId = (int) ($_GET['id'] ?? 0);
        $role = trim($_POST['role'] ?? '');

        if (!in_array($role, ['admin', 'user'], true)) {
            flash('error', 'Invalid role selected.');
            redirect('/admin/users');
        }

        if ($userId === (int) current_user()['id'] && $role !== 'admin') {
            flash('error', 'You cannot remove your own admin access.');
            redirect('/admin/users');
        }

        $this->users->updateRole($userId, $role);
        AdminAuditLog::record('user.role_changed', 'user', $userId, ['role' => $role]);
        flash('success', 'User role updated.');
        redirect('/admin/users');
    }

    public function updateUserStatus(): void
    {
        $this->requireAdmin();
        $userId = (int) ($_GET['id'] ?? 0);
        $status = trim($_POST['account_status'] ?? '');
        $reason = trim($_POST['suspension_reason'] ?? '');
        $notes = trim($_POST['moderation_notes'] ?? '');
        if (!in_array($status, ['pending','active','suspended','banned'], true) || $this->users->findById($userId) === null) {
            flash('error', 'Invalid user or account status.');
            redirect('/admin/users');
        }
        if ($userId === (int) current_user()['id'] && $status !== 'active') {
            flash('error', 'You cannot suspend or ban your own account.');
            redirect('/admin/users');
        }
        if (in_array($status, ['suspended','banned'], true) && mb_strlen($reason) < 5) {
            flash('error', 'Provide a moderation reason of at least 5 characters.');
            redirect('/admin/users');
        }
        $this->users->updateModeration($userId, $status, $reason, $notes);
        AdminAuditLog::record('user.status_changed', 'user', $userId, ['status' => $status, 'reason' => $reason]);
        flash('success', 'Account moderation status updated.');
        redirect('/admin/users');
    }

    public function reports(): void
    {
        $this->requireAdmin();
        $this->view('admin.reports', ['title' => 'Moderation Reports', 'reports' => $this->reports->allForAdmin()]);
    }

    public function updateReport(): void
    {
        $this->requireAdmin();
        $id = (int) ($_GET['id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        $notes = trim($_POST['resolution_notes'] ?? '');
        if (!in_array($status, ['open','reviewing','resolved','dismissed'], true) || $this->reports->find($id) === null) {
            flash('error', 'Invalid report update.');
            redirect('/admin/reports');
        }
        if (in_array($status, ['resolved','dismissed'], true) && mb_strlen($notes) < 5) {
            flash('error', 'Resolution notes must be at least 5 characters.');
            redirect('/admin/reports');
        }
        $this->reports->updateStatus($id, $status, $notes, (int) current_user()['id']);
        AdminAuditLog::record('report.status_changed', 'report', $id, ['status' => $status]);
        flash('success', 'Report updated.');
        redirect('/admin/reports');
    }

    public function auditLogs(): void
    {
        $this->requireAdmin();
        $this->view('admin.audit_logs', ['title' => 'Admin Audit Log', 'logs' => AdminAuditLog::recent()]);
    }

    public function operations(): void
    {
        $this->requireAdmin();
        $this->view('admin.operations', ['title' => 'Production Operations', 'operations' => (new Operations())->snapshot()]);
    }

    public function resolveOperationEvent(): void
    {
        $this->requireAdmin(); $id=(int)($_GET['id']??0); $resolution=trim($_POST['resolution']??'');
        if($id<1||mb_strlen($resolution)<10||mb_strlen($resolution)>1000){flash('error','Provide a resolution of 10-1,000 characters.');redirect('/admin/operations');}
        (new Operations())->resolveEvent($id,$resolution,(int)current_user()['id']); AdminAuditLog::record('system_event.resolved','system_event',$id,['resolution'=>$resolution]); flash('success','System event resolved.'); redirect('/admin/operations');
    }

    public function listings(): void
    {
        $this->requireAdmin();

        $this->view('admin.listings', [
            'title' => 'Manage Listings',
            'listings' => $this->listings->allForAdmin(),
        ]);
    }

    public function updateListingStatus(): void
    {
        $this->requireAdmin();
        $listingId = (int) ($_GET['id'] ?? 0);
        $status = trim($_POST['status'] ?? '');

        if (!in_array($status, ['active', 'reserved', 'sold', 'cancelled'], true)) {
            flash('error', 'Invalid listing status.');
            redirect('/admin/listings');
        }

        $this->listings->updateStatus($listingId, $status);
        AdminAuditLog::record('listing.status_changed', 'listing', $listingId, ['status' => $status]);
        flash('success', 'Listing status updated.');
        redirect('/admin/listings');
    }

    public function prices(): void
    {
        $this->requireAdmin();

        $this->view('admin.prices', [
            'title' => 'Manage Prices',
            'prices' => $this->prices->recent(200),
        ]);
    }

    public function orders(): void
    {
        $this->requireAdmin();

        $this->view('admin.orders', [
            'title' => 'Manage Orders',
            'orders' => $this->orders->allForAdmin(),
        ]);
    }

    public function orderMessages(): void
    {
        $this->requireAdmin();
        $order = $this->orders->find((int) ($_GET['id'] ?? 0));
        if ($order === null || $order['status'] !== 'disputed') {
            http_response_code(404);
            echo '404 - Disputed order messages not found';
            return;
        }
        $this->view('admin.order_messages', [
            'title' => 'Disputed Order Messages',
            'order' => $order,
            'messages' => (new OrderMessage())->forOrder((int) $order['id']),
        ]);
    }

    public function disputes(): void
    {
        $this->requireAdmin();
        $this->view('admin.disputes',['title'=>'Order Disputes','disputes'=>(new Dispute())->allForAdmin()]);
    }

    public function resolveDispute(): void
    {
        $this->requireAdmin();
        $id=(int)($_GET['id']??0); $resolution=trim($_POST['resolution']??''); $refund=(float)($_POST['refund_total']??0); $notes=trim($_POST['resolution_notes']??'');
        if(!in_array($resolution,['full_refund','partial_refund','denied','no_action'],true)||mb_strlen($notes)<10||mb_strlen($notes)>3000){flash('error','Choose a resolution and provide 10-3,000 characters of notes.');redirect('/admin/disputes');}
        try{(new Dispute())->resolve($id,$resolution,$refund,$notes,(int)current_user()['id']);AdminAuditLog::record('dispute.resolved','dispute',$id,['resolution'=>$resolution,'refund_total'=>$refund]);flash('success','Dispute resolved.');}
        catch(RuntimeException $exception){flash('error',$exception->getMessage());}
        redirect('/admin/disputes');
    }

    public function reviews(): void
    {
        $this->requireAdmin();
        $this->view('admin.reviews',['title'=>'Review Moderation','reviews'=>(new MarketplaceReview())->allForAdmin()]);
    }

    public function moderateReview(): void
    {
        $this->requireAdmin();
        $id=(int)($_GET['id']??0);$status=trim($_POST['status']??'');$notes=trim($_POST['moderation_notes']??'');
        if(!in_array($status,['published','hidden'],true)||($status==='hidden'&&mb_strlen($notes)<5)||mb_strlen($notes)>1000){flash('error','Choose a valid review status and explain hidden reviews.');redirect('/admin/reviews');}
        (new MarketplaceReview())->moderate($id,$status,$notes,(int)current_user()['id']);
        AdminAuditLog::record('review.moderated','review',$id,['status'=>$status]);flash('success','Review moderation updated.');redirect('/admin/reviews');
    }

    public function deletePrice(): void
    {
        $this->requireAdmin();
        $this->prices->delete((int) ($_GET['id'] ?? 0));
        AdminAuditLog::record('price.deleted', 'price', (int) ($_GET['id'] ?? 0));

        flash('success', 'Price entry deleted.');
        redirect('/admin/prices');
    }

    public function wallets(): void
    {
        $this->requireAdmin();

        $query = trim($_GET['q'] ?? '');
        $selectedUserId = (int) ($_GET['user_id'] ?? 0);
        $selectedUser = $selectedUserId > 0 ? $this->users->findById($selectedUserId) : null;

        $this->view('admin.wallets', [
            'title' => 'Manage Store Credit',
            'query' => $query,
            'users' => Wallet::usersWithWallets($query),
            'selectedUser' => $selectedUser,
            'selectedWallet' => $selectedUser ? Wallet::getOrCreateByUserId((int) $selectedUser['id']) : null,
            'transactions' => $selectedUser ? Wallet::transactionsByUser((int) $selectedUser['id']) : [],
        ]);
    }

    public function wantedLists(): void
    {
        $this->requireAdmin();

        $this->view('admin.wanted_lists', [
            'title' => 'Wanted Lists',
            'items' => $this->wantedLists->allForAdmin(),
        ]);
    }

    public function adjustWallet(): void
    {
        $this->requireAdmin();

        $userId = (int) ($_POST['user_id'] ?? 0);
        $direction = trim($_POST['direction'] ?? '');
        $amount = (float) ($_POST['amount'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');

        if ($userId < 1 || $this->users->findById($userId) === null) {
            flash('error', 'Choose a valid user.');
            redirect('/admin/wallets');
        }

        if (!in_array($direction, ['credit', 'debit'], true)) {
            flash('error', 'Choose credit or debit.');
            redirect('/admin/wallets?user_id=' . $userId);
        }

        if ($amount <= 0) {
            flash('error', 'Adjustment amount must be greater than zero.');
            redirect('/admin/wallets?user_id=' . $userId);
        }

        if (mb_strlen($notes) < 2 || mb_strlen($notes) > 1000) {
            flash('error', 'Adjustment notes must be between 2 and 1000 characters.');
            redirect('/admin/wallets?user_id=' . $userId);
        }

        try {
            Wallet::adminAdjust($userId, $amount, $direction, $notes, (int) current_user()['id']);
            AdminAuditLog::record('wallet.adjusted', 'user', $userId, ['direction' => $direction, 'amount' => $amount]);
        } catch (Throwable $exception) {
            flash('error', $exception->getMessage());
            redirect('/admin/wallets?user_id=' . $userId);
        }

        flash('success', 'Store credit adjustment saved.');
        redirect('/admin/wallets?user_id=' . $userId);
    }

    private function requireAdmin(): void
    {
        if (!is_admin()) {
            flash('error', 'Admin access is required.');
            redirect(is_logged_in() ? '/' : '/login');
        }
    }
}
