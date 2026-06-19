<?php

require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Report.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'User.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Listing.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'MarketplaceReview.php';

class ReportController extends Controller
{
    public function create(): void
    {
        $this->requireLogin();
        [$type, $subject] = $this->subjectFromRequest();
        $this->view('reports.form', ['title' => 'Submit Report', 'type' => $type, 'subject' => $subject, 'errors' => [], 'old' => []]);
    }

    public function store(): void
    {
        $this->requireLogin();
        [$type, $subject] = $this->subjectFromRequest();
        $reason = trim($_POST['reason'] ?? '');
        $details = trim($_POST['details'] ?? '');
        $errors = [];
        if (!in_array($reason, ['suspected_scam','counterfeit','harassment','inappropriate','other'], true)) {
            $errors[] = 'Choose a valid report reason.';
        }
        if (mb_strlen($details) < 10 || mb_strlen($details) > 2000) {
            $errors[] = 'Details must be between 10 and 2,000 characters.';
        }
        $subjectOwnerId = $type === 'user' ? (int) $subject['id'] : ($type === 'listing' ? (int) $subject['user_id'] : (int) $subject['reviewer_id']);
        if ($subjectOwnerId === (int) current_user()['id']) {
            $errors[] = 'You cannot report your own account, listing, or review.';
        }
        $reports = new Report();
        if ($reports->hasRecentDuplicate((int) current_user()['id'], $type, (int) $subject['id'])) {
            $errors[] = 'You already have an open report for this subject.';
        }
        if ($errors !== []) {
            $this->view('reports.form', ['title' => 'Submit Report', 'type' => $type, 'subject' => $subject, 'errors' => $errors, 'old' => compact('reason','details')]);
            return;
        }
        $reports->create(['reporter_id' => (int) current_user()['id'], 'subject_type' => $type, 'subject_id' => (int) $subject['id'], 'reason' => $reason, 'details' => $details]);
        flash('success', 'Report submitted for moderator review.');
        redirect($type === 'user' ? '/sellers/show?id=' . (int) $subject['id'] : ($type === 'review' ? '/sellers/show?id=' . (int) $subject['seller_id'] : '/listings'));
    }

    private function subjectFromRequest(): array
    {
        $type = trim($_GET['type'] ?? '');
        $id = (int) ($_GET['id'] ?? 0);
        $subject = $type === 'user' ? (new User())->findById($id) : ($type === 'listing' ? (new Listing())->find($id) : ($type === 'review' ? (new MarketplaceReview())->find($id) : null));
        if ($subject === null) {
            http_response_code(404);
            echo '404 - Report subject not found';
            exit;
        }
        return [$type, $subject];
    }

    private function requireLogin(): void
    {
        if (!is_logged_in()) {
            flash('error', 'Please log in to submit a report.');
            redirect('/login');
        }
    }
}
