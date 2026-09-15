<?php
define('PAGE_TITLE', 'Contact Messages - HR Auto Mobile Admin');
require_once __DIR__ . '/includes/auth.php';

// Handle POST actions
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'] ?? '';

    // Update Status
    if ($action === 'update_status') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $newStatus = $_POST['status'] ?? 'read';
        if ($id && in_array($newStatus, ['unread', 'read', 'replied'], true)) {
            $stmt = $pdo->prepare("UPDATE contact_messages SET status = :status WHERE id = :id");
            $stmt->execute(['status' => $newStatus, 'id' => $id]);
            setFlashMessage('success', "Message status updated to " . ucfirst($newStatus) . ".");
        }
        header("Location: messages.php");
        exit;
    }

    // Delete Message
    if ($action === 'delete') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if ($id) {
            $pdo->prepare("DELETE FROM contact_messages WHERE id = :id")->execute(['id' => $id]);
            setFlashMessage('success', "Contact message deleted.");
        }
        header("Location: messages.php");
        exit;
    }
}

// Filter parameter
$filterStatus = $_GET['status'] ?? 'all';

$sql = "SELECT * FROM contact_messages WHERE 1=1";
$params = [];

if ($filterStatus !== 'all' && in_array($filterStatus, ['unread', 'read', 'replied'], true)) {
    $sql .= " AND status = :status";
    $params['status'] = $filterStatus;
}

$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$messages = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><i class="fas fa-envelope mr-2 text-primary"></i> Contact Inquiries</h1>
                </div>
                <div class="col-sm-6 text-right">
                    <span class="badge badge-light border p-2">Total: <?= count($messages) ?> inquiries</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">

            <!-- Filter Badges -->
            <div class="card card-outline card-primary shadow-sm mb-3">
                <div class="card-body py-2">
                    <div class="d-flex align-items-center">
                        <span class="mr-3 text-muted font-weight-bold"><i class="fas fa-filter mr-1"></i> Filter Status:</span>
                        <a href="messages.php" class="btn btn-sm <?= ($filterStatus === 'all') ? 'btn-primary font-weight-bold' : 'btn-outline-secondary' ?> mr-2">All Messages</a>
                        <a href="messages.php?status=unread" class="btn btn-sm <?= ($filterStatus === 'unread') ? 'btn-danger font-weight-bold' : 'btn-outline-danger' ?> mr-2">
                            <i class="fas fa-envelope mr-1"></i> Unread
                        </a>
                        <a href="messages.php?status=read" class="btn btn-sm <?= ($filterStatus === 'read') ? 'btn-info font-weight-bold' : 'btn-outline-info' ?> mr-2">
                            <i class="fas fa-envelope-open mr-1"></i> Read
                        </a>
                        <a href="messages.php?status=replied" class="btn btn-sm <?= ($filterStatus === 'replied') ? 'btn-success font-weight-bold' : 'btn-outline-success' ?>">
                            <i class="fas fa-reply mr-1"></i> Replied
                        </a>
                    </div>
                </div>
            </div>

            <!-- Messages DataTable -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h3 class="card-title font-weight-bold"><i class="fas fa-inbox mr-1"></i> Customer Messages Inbox</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped table-hover datatable-buttons">
                        <thead>
                            <tr>
                                <th style="width: 50px;">ID</th>
                                <th>Sender Info</th>
                                <th>Message Snippet</th>
                                <th>Received Time</th>
                                <th>Status</th>
                                <th style="width: 150px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($messages as $m): ?>
                                <tr class="<?= (($m['status'] ?? 'unread') === 'unread') ? 'font-weight-bold table-warning' : '' ?>">
                                    <td><strong>#<?= $m['id'] ?></strong></td>
                                    <td>
                                        <strong><?= htmlspecialchars($m['name']) ?></strong><br>
                                        <small class="text-muted"><i class="fas fa-phone mr-1"></i><?= htmlspecialchars($m['phone']) ?></small><br>
                                        <small class="text-muted"><i class="fas fa-envelope mr-1"></i><?= htmlspecialchars($m['email']) ?></small>
                                    </td>
                                    <td>
                                        <p class="mb-0 text-dark"><?= htmlspecialchars(mb_strimwidth($m['message'], 0, 100, "...")) ?></p>
                                    </td>
                                    <td>
                                        <small><?= date('d M Y, h:i A', strtotime($m['created_at'])) ?></small>
                                    </td>
                                    <td>
                                        <form method="POST" style="display:inline-block;">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                            <select name="status" class="form-control form-control-sm border-0 font-weight-bold <?= (($m['status'] ?? 'unread') === 'unread') ? 'badge badge-danger' : ((($m['status'] ?? '') === 'replied') ? 'badge badge-success' : 'badge badge-secondary') ?>" onchange="this.form.submit()">
                                                <option value="unread" <?= (($m['status'] ?? 'unread') === 'unread') ? 'selected' : '' ?>>Unread</option>
                                                <option value="read" <?= (($m['status'] ?? '') === 'read') ? 'selected' : '' ?>>Read</option>
                                                <option value="replied" <?= (($m['status'] ?? '') === 'replied') ? 'selected' : '' ?>>Replied</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-xs btn-primary" onclick='viewMessage(<?= json_encode($m) ?>)' title="View Full Message">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <a href="mailto:<?= htmlspecialchars($m['email']) ?>?subject=Regarding your inquiry at HR Auto Mobile" class="btn btn-xs btn-success" title="Reply via Email">
                                            <i class="fas fa-reply"></i>
                                        </a>
                                        <form method="POST" style="display:inline-block;" onsubmit="return confirmAction(event, 'Are you sure you want to delete message from \'<?= htmlspecialchars($m['name']) ?>\'?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                            <button type="submit" class="btn btn-xs btn-danger" title="Delete Message">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </section>
</div>

<!-- Modal: View Message -->
<div class="modal fade" id="viewMessageModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title font-weight-bold" id="msgModalTitle">Customer Inquiry</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="msgModalBody">
                <!-- Populated dynamically -->
            </div>
            <div class="modal-footer justify-content-between">
                <form method="POST" id="markReadForm">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="id" id="modal_msg_id">
                    <input type="hidden" name="status" value="read">
                    <button type="submit" class="btn btn-outline-secondary btn-sm"><i class="fas fa-check mr-1"></i> Mark as Read</button>
                </form>
                <div>
                    <a href="#" id="modalReplyBtn" class="btn btn-success btn-sm"><i class="fas fa-reply mr-1"></i> Reply via Email</a>
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
function viewMessage(m) {
    $('#msgModalTitle').text('Message from ' + m.name);
    $('#modal_msg_id').val(m.id);
    $('#modalReplyBtn').attr('href', 'mailto:' + m.email + '?subject=Re: Inquiry at HR Auto Mobile');

    var html = `
        <div class="mb-3">
            <p class="mb-1"><strong>Sender:</strong> ${m.name}</p>
            <p class="mb-1"><strong>Email:</strong> <a href="mailto:${m.email}">${m.email}</a></p>
            <p class="mb-1"><strong>Phone:</strong> <a href="tel:${m.phone}">${m.phone}</a></p>
            <p class="mb-1"><strong>Date:</strong> ${m.created_at}</p>
        </div>
        <hr>
        <div class="alert alert-light border p-3" style="white-space: pre-wrap; font-size: 15px; line-height: 1.6;">${m.message}</div>
    `;
    $('#msgModalBody').html(html);
    $('#viewMessageModal').modal('show');
}
</script>
