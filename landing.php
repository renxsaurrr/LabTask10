<?php

require 'insert.php';
require 'update.php';
require 'delete.php';
require 'select.php';

$editUser = null;
if (isset($_GET['edit'])) {
  $user_id = $_GET['edit'];
  $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
  $stmt->execute([$user_id]);
  $editUser = $stmt->fetch(PDO::FETCH_ASSOC);
}

$totalRevenue = array_sum(array_column($users, 'amount'));
$avgOrder     = count($users) ? $totalRevenue / count($users) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Orders Dashboard</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <style>
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f1f5f9;
    }

    /* Navbar */
    .navbar { box-shadow: 0 1px 4px rgba(0,0,0,.08); }

    /* Stat cards */
    .stat-card .stat-value {
      font-size: 1.75rem;
      font-weight: 700;
      line-height: 1;
    }
    .stat-card .stat-label {
      font-size: .72rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: .06em;
    }
    .stat-icon {
      width: 44px; height: 44px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.25rem;
    }

    /* Avatar */
    .avatar {
      width: 36px; height: 36px;
      border-radius: 50%;
      background: linear-gradient(135deg, #0d6efd, #6f42c1);
      color: #fff;
      font-size: .78rem;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
      text-transform: uppercase;
      flex-shrink: 0;
    }

    /* Table tweaks */
    .table thead th {
      font-size: .7rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .07em;
      color: #6c757d;
      border-bottom-width: 2px;
      background-color: #f8f9fa;
    }
    .table tbody td { vertical-align: middle; }
    .table tbody tr:hover { background-color: #f8f9fb; }

    /* Amount */
    .amount { font-family: monospace; font-weight: 600; color: #198754; }

    /* Form label style */
    .form-label {
      font-size: .72rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .06em;
      color: #6c757d;
      margin-bottom: .3rem;
    }

    /* Card header custom */
    .card-header-custom {
      background: #fff;
      border-bottom: 1px solid #e9ecef;
      padding: 1rem 1.25rem;
    }

    /* Fade-in animation */
    .fade-in {
      animation: fadeUp .35s ease both;
    }
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(10px); }
      to   { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
  <div class="container-fluid px-4">
    <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="#">
      <i class="bi bi-box-seam-fill"></i>
      Orders Dashboard
    </a>
    <span class="badge bg-light text-primary ms-auto">PDO CRUD</span>
  </div>
</nav>

<div class="container-fluid px-4 py-4" style="max-width:1300px">

  <!-- STAT CARDS -->
  <div class="row g-3 mb-4 fade-in">
    <div class="col-12 col-sm-4">
      <div class="card border-0 shadow-sm stat-card h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="stat-icon bg-primary bg-opacity-10 text-primary">
            <i class="bi bi-people-fill"></i>
          </div>
          <div>
            <div class="stat-label text-muted">Total Records</div>
            <div class="stat-value text-primary"><?= count($users) ?></div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-4">
      <div class="card border-0 shadow-sm stat-card h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="stat-icon bg-success bg-opacity-10 text-success">
            <i class="bi bi-currency-dollar"></i>
          </div>
          <div>
            <div class="stat-label text-muted">Total Revenue</div>
            <div class="stat-value text-success">$<?= number_format($totalRevenue, 2) ?></div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-4">
      <div class="card border-0 shadow-sm stat-card h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="stat-icon bg-warning bg-opacity-10 text-warning">
            <i class="bi bi-graph-up-arrow"></i>
          </div>
          <div>
            <div class="stat-label text-muted">Avg. Order</div>
            <div class="stat-value text-warning">$<?= number_format($avgOrder, 2) ?></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- MAIN ROW: FORM + TABLE -->
  <div class="row g-4 align-items-start">

    <!-- FORM CARD -->
    <div class="col-12 col-lg-4 fade-in" style="animation-delay:.05s">
      <div class="card border-0 shadow-sm">
        <div class="card-header-custom d-flex align-items-center gap-2">
          <div class="stat-icon bg-primary bg-opacity-10 text-primary" style="width:34px;height:34px;font-size:1rem;border-radius:8px">
            <i class="bi <?= $editUser ? 'bi-pencil-fill' : 'bi-plus-lg' ?>"></i>
          </div>
          <div>
            <div class="fw-semibold" style="font-size:.95rem">
              <?= $editUser ? 'Edit Record' : 'Add New Entry' ?>
            </div>
            <div class="text-muted" style="font-size:.75rem">
              <?= $editUser ? 'Update the fields below' : 'Fill in user & order info' ?>
            </div>
          </div>
        </div>
        <div class="card-body p-3">

          <?php if (isset($_POST['add']) || isset($_POST['update'])): ?>
            <div class="alert alert-success d-flex align-items-center gap-2 py-2 px-3" role="alert">
              <i class="bi bi-check-circle-fill"></i>
              Record saved successfully.
            </div>
          <?php endif; ?>

          <form method="POST" action="landing.php">
            <?php if (!empty($editUser)): ?>
              <input type="hidden" name="user_id" value="<?= htmlspecialchars($editUser['user_id']) ?>">
            <?php endif; ?>

            <div class="mb-3">
              <label class="form-label">Full Name</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <input type="text" name="name" class="form-control" placeholder="e.g. Jane Doe"
                  value="<?= !empty($editUser) ? htmlspecialchars($editUser['name']) : '' ?>" required>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label">Email Address</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" class="form-control" placeholder="e.g. jane@example.com"
                  value="<?= !empty($editUser) ? htmlspecialchars($editUser['email']) : '' ?>" required>
              </div>
            </div>

            <hr class="my-3">

            <div class="row g-2 mb-3">
              <div class="col-7">
                <label class="form-label">Product</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-box"></i></span>
                  <input type="text" name="product" class="form-control" placeholder="e.g. Keyboard" required>
                </div>
              </div>
              <div class="col-5">
                <label class="form-label">Amount</label>
                <div class="input-group">
                  <span class="input-group-text">$</span>
                  <input type="number" step="0.01" name="amount" class="form-control" placeholder="0.00" required>
                </div>
              </div>
            </div>

            <?php if (!empty($editUser)): ?>
              <div class="d-grid gap-2">
                <button type="submit" name="update" class="btn btn-warning fw-semibold">
                  <i class="bi bi-floppy-fill me-1"></i> Save Changes
                </button>
                <a href="landing.php" class="btn btn-outline-secondary fw-semibold">
                  <i class="bi bi-x-lg me-1"></i> Cancel
                </a>
              </div>
            <?php else: ?>
              <div class="d-grid">
                <button type="submit" name="add" class="btn btn-primary fw-semibold">
                  <i class="bi bi-plus-circle-fill me-1"></i> Add Entry
                </button>
              </div>
            <?php endif; ?>
          </form>
        </div>
      </div>
    </div>

    <!-- TABLE CARD -->
    <div class="col-12 col-lg-8 fade-in" style="animation-delay:.1s">
      <div class="card border-0 shadow-sm">
        <div class="card-header-custom d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <div class="stat-icon bg-primary bg-opacity-10 text-primary" style="width:34px;height:34px;font-size:1rem;border-radius:8px">
              <i class="bi bi-table"></i>
            </div>
            <div>
              <div class="fw-semibold" style="font-size:.95rem">User & Order List</div>
              <div class="text-muted" style="font-size:.75rem"><?= count($users) ?> record<?= count($users) !== 1 ? 's' : '' ?> found</div>
            </div>
          </div>
        </div>

        <div class="table-responsive">
          <?php if (empty($users)): ?>
            <div class="text-center py-5 text-muted">
              <i class="bi bi-inbox" style="font-size:2.5rem;opacity:.35"></i>
              <p class="mt-2 mb-0">No records yet. Add one using the form.</p>
            </div>
          <?php else: ?>
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>ID</th>
                <th>User</th>
                <th>Product</th>
                <th>Amount</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $user): ?>
              <tr>
                <td>
                  <span class="badge bg-light text-secondary border fw-normal font-monospace">
                    #<?= htmlspecialchars($user['user_id']) ?>
                  </span>
                </td>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div class="avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
                    <div>
                      <div class="fw-semibold" style="font-size:.875rem"><?= htmlspecialchars($user['name']) ?></div>
                      <div class="text-muted font-monospace" style="font-size:.72rem"><?= htmlspecialchars($user['email']) ?></div>
                    </div>
                  </div>
                </td>
                <td>
                  <?php if (!empty($user['product'])): ?>
                    <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary fw-semibold" style="font-size:.75rem">
                      <?= htmlspecialchars($user['product']) ?>
                    </span>
                  <?php else: ?>
                    <span class="badge rounded-pill bg-light text-muted fw-normal">N/A</span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="amount">
                    <?= !empty($user['amount']) ? '$' . number_format($user['amount'], 2) : '—' ?>
                  </span>
                </td>
                <td>
                  <div class="d-flex gap-1">
                    <a href="?edit=<?= $user['user_id'] ?>" class="btn btn-sm btn-outline-primary">
                      <i class="bi bi-pencil-fill"></i> Edit
                    </a>
                    <a href="?delete=<?= $user['user_id'] ?>"
                       class="btn btn-sm btn-outline-danger"
                       onclick="return confirm('Delete this record?')">
                      <i class="bi bi-trash-fill"></i> Delete
                    </a>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>