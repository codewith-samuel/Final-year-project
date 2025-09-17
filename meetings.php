<?php
session_start();
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['member', 'chairperson', 'secretary', 'superadmin']) || !isset($_SESSION['id'])) {
    header("Location: /Chama-management-system/signin.php");
    exit;
}

include('db_connect.php');

// Handle meeting creation (Secretary or Superadmin only)
if (in_array($_SESSION['role'], ['secretary', 'superadmin']) && isset($_POST['create_meeting'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $meeting_date = $_POST['meeting_date'];
    $online_link = !empty($_POST['online_link']) ? $_POST['online_link'] : NULL;

    // Validate meeting date is in the future
    if (strtotime($meeting_date) <= time()) {
        $_SESSION['error'] = "Meeting date must be in the future.";
        header("Location: meetings.php");
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO meetings (title, description, meeting_date, online_link, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssss", $title, $description, $meeting_date, $online_link);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Meeting created successfully.";
    } else {
        $_SESSION['error'] = "Error creating meeting: " . $stmt->error;
    }
    $stmt->close();
    header("Location: meetings.php");
    exit;
}

// Determine view mode
$view_mode = $_GET['view'] ?? '';
$is_manage_view = in_array($_SESSION['role'], ['secretary', 'superadmin']) && empty($view_mode);

// Fetch meetings based on view
$meetings = null;
if ($view_mode === 'past') {
    $query = "SELECT * FROM meetings WHERE meeting_date < NOW() ORDER BY meeting_date DESC";
    // Alternative: Use DATE(meeting_date) < CURDATE() to consider entire days
    // $query = "SELECT * FROM meetings WHERE DATE(meeting_date) < CURDATE() ORDER BY meeting_date DESC";
    $meetings = $conn->query($query);
} elseif ($view_mode === 'upcoming' || !$is_manage_view) {
    $query = "SELECT * FROM meetings WHERE DATE(meeting_date) >= CURDATE() ORDER BY meeting_date ASC";
    $meetings = $conn->query($query);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meetings - Chama <?php echo $_SESSION['role'] === 'superadmin' ? 'Super Admin' : ($_SESSION['role'] === 'secretary' ? 'Secretary' : ($_SESSION['role'] === 'chairperson' ? 'Chairperson' : 'Member')); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/marked/4.0.2/marked.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background: #f4f7fa; color: #333; transition: all 0.3s; }
        .sidebar { width: 250px; height: 100vh; background: #2c3e50; position: fixed; top: 0; left: 0; padding: 20px; color: #ecf0f1; box-shadow: 2px 0 5px rgba(0,0,0,0.1); }
        .sidebar h1 { font-size: 24px; margin-bottom: 30px; }
        .sidebar a { display: block; color: #ecf0f1; text-decoration: none; padding: 10px; margin: 5px 0; border-radius: 5px; }
        .sidebar a:hover { background: #34495e; }
        .container { margin-left: 270px; padding: 40px; min-height: 100vh; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header h2 { font-size: 28px; color: #2c3e50; }
        .form-wrapper, .table-wrapper { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .form-wrapper input, .form-wrapper textarea, .form-wrapper select { display: block; width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; }
        .form-wrapper input[type="checkbox"] { display: inline-block; width: auto; margin: 10px 5px; }
        .form-wrapper button { padding: 10px 20px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .form-wrapper button:hover { background: #2980b9; }
        .form-wrapper button:disabled { background: #95a5a6; cursor: not-allowed; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #3498db; color: white; cursor: pointer; }
        th:hover { background: #2980b9; }
        tr:hover { background: #f9f9f9; }
        .join-link { color: #3498db; text-decoration: none; font-weight: bold; }
        .join-link:hover { color: #2980b9; text-decoration: underline; }
        .no-meetings { text-align: center; color: #7f8c8d; font-size: 18px; padding: 20px; }
        .alert-success { margin-bottom: 20px; padding: 10px; background: #dff0d8; color: #3c763d; border-radius: 5px; }
        .alert-error { margin-bottom: 20px; padding: 10px; background: #f2dede; color: #a94442; border-radius: 5px; }
        .has-apology { background: #e8f4f8; }
        .apology-approved { color: #27ae60; font-weight: bold; }
        .apology-none { color: #7f8c8d; }
        .member-purpose { position: relative; max-width: 300px; }
        .member-purpose.short { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .member-purpose.full { display: none; }
        .read-more { color: #3498db; cursor: pointer; font-size: 14px; }
        .read-more:hover { text-decoration: underline; }
        .purpose-tooltip { position: absolute; top: -10px; left: 50%; transform: translateX(-50%); background: #333; color: white; padding: 8px; border-radius: 4px; font-size: 12px; z-index: 10; display: none; max-width: 250px; white-space: normal; }
        .member-purpose:hover .purpose-tooltip { display: block; }
        body.dark { background: #1a1a1a; color: #ecf0f1; }
        body.dark .sidebar { background: #121212; }
        body.dark .container { background: #1a1a1a; }
        body.dark .form-wrapper, body.dark .table-wrapper { background: #2c2c2c; }
        body.dark th { background: #e74c3c; }
        body.dark th:hover { background: #c0392b; }
        body.dark tr:hover { background: #333; }
        body.dark .form-wrapper button { background: #e74c3c; }
        body.dark .form-wrapper button:hover { background: #c0392b; }
        body.dark .join-link { color: #e74c3c; }
        body.dark .join-link:hover { color: #c0392b; }
        body.dark .no-meetings { color: #bdc3c7; }
        body.dark .alert-success { background: #3c763d; color: #dff0d8; }
        body.dark .alert-error { background: #a94442; color: #f2dede; }
        body.dark .has-apology { background: #2a3b4c; }
        body.dark .apology-approved { color: #2ecc71; }
        body.dark .apology-none { color: #95a5a6; }
        body.dark .read-more { color: #e74c3c; }
        body.dark .purpose-tooltip { background: #444; }
        @media (max-width: 768px) { 
            .sidebar { width: 200px; } 
            .container { margin-left: 220px; } 
            .member-purpose { max-width: 200px; } 
        }
        @media (max-width: 480px) { 
            .sidebar { position: static; width: 100%; height: auto; } 
            .container { margin-left: 0; padding: 20px; } 
            table { font-size: 14px; } 
            .member-purpose { max-width: 150px; font-size: 13px; } 
            .purpose-tooltip { font-size: 11px; max-width: 200px; } 
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h1>Chama <?php echo $_SESSION['role'] === 'superadmin' ? 'Secretary' : ($_SESSION['role'] === 'secretary' ? 'Secretary' : ($_SESSION['role'] === 'chairperson' ? 'Chairperson' : 'Member')); ?></h1>
        <?php if ($_SESSION['role'] === 'superadmin'): ?>
            <a href="/Chama-management-system/admin_dashboard.php">Secretary Dashboard</a>
            <a href="/Chama-management-system/meetings.php?view=upcoming">Upcoming Meetings</a>
            <a href="/Chama-management-system/meetings.php?view=past">Past Meetings</a>
            <a href="/Chama-management-system/meetings.php">Manage Meetings</a>
            <a href="/Chama-management-system/manage_apologies.php">Manage Apologies</a>
        <?php elseif ($_SESSION['role'] === 'secretary'): ?>
            <a href="/Chama-management-system/secretary_dashboard.php">Secretary Dashboard</a>
            <a href="/Chama-management-system/meetings.php?view=upcoming">Upcoming Meetings</a>
            <a href="/Chama-management-system/meetings.php?view=past">Past Meetings</a>
            <a href="/Chama-management-system/meetings.php">Manage Meetings</a>
            <a href="/Chama-management-system/send_apology.php">Send Apology</a>
        <?php elseif ($_SESSION['role'] === 'chairperson'): ?>
            <a href="/Chama-management-system/chairperson_dashboard.php">Chairperson Dashboard</a>
            <a href="/Chama-management-system/meetings.php?view=upcoming">Upcoming Meetings</a>
            <a href="/Chama-management-system/meetings.php?view=past">Past Meetings</a>
            <a href="/Chama-management-system/send_apology.php">Send Apology</a>
            <a href="/Chama-management-system/manage_apologies.php">Manage Apologies</a>
        <?php else: ?>
            <a href="/Chama-management-system/member_dashboard.php">Dashboard</a>
            <a href="/Chama-management-system/meetings.php?view=upcoming">Upcoming Meetings</a>
            <a href="/Chama-management-system/meetings.php?view=past">Past Meetings</a>
            <a href="/Chama-management-system/send_apology.php">Send Apology</a>
            <a href="/Chama-management-system/loans.php">Loans</a>
            <a href="/Chama-management-system/receipts.php">Receipts</a>
        <?php endif; ?>
        <a href="/Chama-management-system/logout.php">Logout</a>
    </div>
    <div class="container">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-success">
                <?php echo htmlspecialchars($_SESSION['success']); ?>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php elseif (isset($_SESSION['error'])): ?>
            <div class="alert-error">
                <?php echo htmlspecialchars($_SESSION['error']); ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        <div class="header">
            <h2><?php echo $is_manage_view ? 'Manage Meetings' : ($view_mode === 'past' ? 'Past Meetings' : 'Upcoming Meetings'); ?></h2>
        </div>
        <?php if ($is_manage_view): ?>
            <div class="form-wrapper">
                <h3>Schedule a Meeting</h3>
                <form method="POST" action="">
                    <input type="text" name="title" placeholder="Meeting Title" required>
                    <textarea name="description" placeholder="Meeting Purpose (use *bold* for emphasis, - for bullet points)" rows="3"></textarea>
                    <input type="datetime-local" name="meeting_date" id="meeting_date" required>
                    <input type="url" name="online_link" placeholder="Online Meeting Link (e.g., Zoom, Meet)">
                    <button type="submit" name="create_meeting">Create Meeting</button>
                </form>
            </div>
        <?php endif; ?>
        <?php if ($meetings): ?>
            <div class="table-wrapper">
                <h3><?php echo $view_mode === 'past' ? 'Past Meetings' : 'Upcoming Meetings'; ?></h3>
                <table id="meetings-table">
                    <thead>
                        <tr>
                            <th onclick="sortTable('meetings-table', 0)">ID</th>
                            <th onclick="sortTable('meetings-table', 1)">Title</th>
                            <th onclick="sortTable('meetings-table', 2)">Date</th>
                            <th><?php echo $_SESSION['role'] === 'member' && $view_mode !== 'past' ? 'Purpose' : 'Description'; ?></th>
                            <th>Join</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($meetings->num_rows > 0): ?>
                            <?php while ($row = $meetings->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td><?php echo htmlspecialchars($row['meeting_date']); ?></td>
                                    <td>
                                        <?php if ($_SESSION['role'] === 'member' && $view_mode !== 'past'): ?>
                                            <div class="member-purpose <?php echo strlen($row['description']) > 100 ? 'short' : ''; ?>" data-full-text="<?php echo htmlspecialchars($row['description'] ?: 'N/A'); ?>">
                                                <span class="short-text"><?php echo htmlspecialchars(substr($row['description'], 0, 100)) . (strlen($row['description']) > 100 ? '...' : ''); ?></span>
                                                <div class="full-text markdown-content"></div>
                                                <?php if (strlen($row['description']) > 100): ?>
                                                    <span class="read-more">Read More</span>
                                                <?php endif; ?>
                                                <div class="purpose-tooltip"><?php echo htmlspecialchars($row['description'] ?: 'N/A'); ?></div>
                                            </div>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($row['description'] ?: 'N/A'); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($row['online_link'] && $view_mode !== 'past'): ?>
                                            <a href="/Chama-management-system/join_meeting.php?id=<?php echo $row['id']; ?>" target="_blank" class="join-link">Join Online</a>
                                        <?php elseif ($row['online_link']): ?>
                                            <a href="<?php echo htmlspecialchars($row['online_link']); ?>" target="_blank" class="join-link">View Link</a>
                                        <?php else: ?>
                                            In-person
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="no-meetings">No <?php echo $view_mode === 'past' ? 'past' : 'upcoming'; ?> meetings</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <?php if ($is_manage_view): ?>
            <div class="table-wrapper">
                <h3>Online Attendance</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Meeting</th>
                            <th>Member</th>
                            <th>Attended At</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $online_attendance = $conn->query("SELECT m.title, CONCAT(u.first_name, ' ', u.last_name) AS member_name, a.attended_at, a.status 
                                                          FROM attendance a 
                                                          JOIN meetings m ON a.meeting_id = m.id 
                                                          JOIN users u ON a.user_id = u.id 
                                                          ORDER BY a.attended_at DESC");
                        if ($online_attendance->num_rows > 0):
                            while ($row = $online_attendance->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td><?php echo htmlspecialchars($row['member_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['attended_at']); ?></td>
                                    <td><?php echo htmlspecialchars($row['status']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="no-meetings">No online attendance records</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="table-wrapper">
                <h3>Physical Attendance</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Meeting</th>
                            <th>Member</th>
                            <th>Status</th>
                            <th>Recorded At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $physical_attendance = $conn->query("SELECT m.title, CONCAT(u.first_name, ' ', u.last_name) AS member_name, ma.is_present, ma.has_apology, ma.recorded_at 
                                                            FROM meeting_attendance ma 
                                                            JOIN meetings m ON ma.meeting_id = m.id 
                                                            JOIN users u ON ma.user_id = u.id 
                                                            ORDER BY ma.recorded_at DESC");
                        if ($physical_attendance->num_rows > 0):
                            while ($row = $physical_attendance->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td><?php echo htmlspecialchars($row['member_name']); ?></td>
                                    <td><?php echo $row['has_apology'] ? 'Absent with Apology' : ($row['is_present'] ? 'Present' : 'Absent'); ?></td>
                                    <td><?php echo htmlspecialchars($row['recorded_at']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="no-meetings">No physical attendance records</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="form-wrapper">
                <h3>Record Physical Attendance</h3>
                <?php
                $members_query = $conn->query("SELECT id, username, first_name, last_name FROM users WHERE role IN ('member', 'chairperson', 'secretary', 'superadmin')");
                $members = $members_query->fetch_all(MYSQLI_ASSOC);
                $meetings_query = $conn->query("SELECT id, title, meeting_date FROM meetings WHERE online_link IS NULL AND DATE(meeting_date) >= CURDATE() ORDER BY meeting_date ASC");
                $meetings = $meetings_query->fetch_all(MYSQLI_ASSOC);
                ?>
                <form method="POST" action="/Chama-management-system/record_attendance.php" id="attendance-form">
                    <select name="meeting_id" id="meeting_id" required onchange="checkAttendance(this.value)">
                        <option value="" disabled selected>Choose an in-person meeting</option>
                        <?php foreach ($meetings as $meeting): ?>
                            <option value="<?php echo $meeting['id']; ?>">
                                <?php echo htmlspecialchars($meeting['title'] . ' (' . date('d M Y, H:i', strtotime($meeting['meeting_date'])) . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="attendance-message" style="display: none; margin: 10px 0;"></div>
                    <table>
                        <thead>
                            <tr>
                                <th>Member</th>
                                <th>Present</th>
                                <th>Absent</th>
                                <th>Absent with Apology</th>
                                <th>Apology Status</th>
                            </tr>
                        </thead>
                        <tbody id="attendance-table">
                            <?php foreach ($members as $member): ?>
                                <tr id="member-<?php echo $member['id']; ?>">
                                    <td><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?></td>
                                    <td><input type="checkbox" name="attendance[<?php echo $member['id']; ?>][present]" value="1" class="present-checkbox"></td>
                                    <td><input type="checkbox" name="attendance[<?php echo $member['id']; ?>][absent]" value="1" class="absent-checkbox"></td>
                                    <td><input type="checkbox" name="attendance[<?php echo $member['id']; ?>][has_apology]" value="1" class="apology-checkbox" disabled></td>
                                    <td><span class="apology-status" id="apology-<?php echo $member['id']; ?>"></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <button type="submit" id="submit-attendance" disabled>Record Attendance</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
    <script>
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark');
        }
        function sortTable(tableId, col) {
            const table = document.getElementById(tableId);
            const rows = Array.from(table.tBodies[0].rows);
            const isNumeric = col === 0;
            rows.sort((a, b) => {
                const aVal = a.cells[col].textContent;
                const bVal = b.cells[col].textContent;
                if (isNumeric) return aVal - bVal;
                if (col === 2) return new Date(aVal) - new Date(bVal);
                return aVal.localeCompare(bVal);
            });
            rows.forEach(row => table.tBodies[0].appendChild(row));
        }
        document.addEventListener('DOMContentLoaded', () => {
            const meetingDateInput = document.getElementById('meeting_date');
            if (meetingDateInput) {
                const now = new Date();
                const offset = now.getTimezoneOffset();
                now.setMinutes(now.getMinutes() - offset);
                const minDateTime = now.toISOString().slice(0, 16);
                meetingDateInput.min = minDateTime;
            }
            document.querySelectorAll('#attendance-table tr').forEach(row => {
                const present = row.querySelector('.present-checkbox');
                const absent = row.querySelector('.absent-checkbox');
                const apology = row.querySelector('.apology-checkbox');
                [present, absent, apology].forEach(checkbox => {
                    if (checkbox) {
                        checkbox.addEventListener('change', () => {
                            if (checkbox.checked) {
                                [present, absent, apology].forEach(other => {
                                    if (other !== checkbox && other) other.checked = false;
                                });
                            }
                        });
                    }
                });
            });
            document.querySelectorAll('.member-purpose').forEach(wrapper => {
                const fullText = wrapper.getAttribute('data-full-text') || 'N/A';
                const fullTextDiv = wrapper.querySelector('.full-text');
                if (fullTextDiv) {
                    fullTextDiv.innerHTML = marked.parse(fullText);
                }
            });
            document.querySelectorAll('.read-more').forEach(link => {
                link.addEventListener('click', () => {
                    const wrapper = link.closest('.member-purpose');
                    const shortText = wrapper.querySelector('.short-text');
                    const fullText = wrapper.querySelector('.full-text');
                    if (wrapper.classList.contains('short')) {
                        wrapper.classList.remove('short');
                        shortText.style.display = 'none';
                        fullText.style.display = 'block';
                        link.textContent = 'Read Less';
                    } else {
                        wrapper.classList.add('short');
                        shortText.style.display = 'block';
                        fullText.style.display = 'none';
                        link.textContent = 'Read More';
                    }
                });
            });
        });
        function checkAttendance(meetingId) {
            const messageDiv = document.getElementById('attendance-message');
            const submitButton = document.getElementById('submit-attendance');
            const form = document.getElementById('attendance-form');
            messageDiv.style.display = 'none';
            submitButton.disabled = true;
            form.querySelectorAll('.present-checkbox, .absent-checkbox').forEach(el => el.disabled = false);
            form.querySelectorAll('.apology-checkbox').forEach(el => {
                el.disabled = true;
                el.checked = false;
            });
            form.querySelectorAll('.apology-status').forEach(el => el.textContent = '');
            form.querySelectorAll('tr').forEach(tr => tr.classList.remove('has-apology'));

            if (!meetingId) return;

            fetch(`/Chama-management-system/check_attendance.php?meeting_id=${meetingId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.exists) {
                        messageDiv.style.display = 'block';
                        messageDiv.className = 'alert-error';
                        messageDiv.textContent = 'Attendance for this meeting has already been recorded.';
                    } else {
                        submitButton.disabled = false;
                        fetch(`/Chama-management-system/get_apologies.php?meeting_id=${meetingId}`)
                            .then(response => response.json())
                            .then(apologies => {
                                apologies.forEach(apology => {
                                    const row = document.getElementById(`member-${apology.user_id}`);
                                    if (row) {
                                        const statusEl = document.getElementById(`apology-${apology.user_id}`);
                                        const apologyCheckbox = row.querySelector('.apology-checkbox');
                                        const presentCheckbox = row.querySelector('.present-checkbox');
                                        const absentCheckbox = row.querySelector('.absent-checkbox');
                                        if (statusEl && apologyCheckbox && presentCheckbox && absentCheckbox) {
                                            statusEl.textContent = 'Approved Apology';
                                            statusEl.className = 'apology-status apology-approved';
                                            apologyCheckbox.disabled = false;
                                            apologyCheckbox.checked = true;
                                            presentCheckbox.disabled = true;
                                            absentCheckbox.disabled = true;
                                            row.classList.add('has-apology');
                                        }
                                    }
                                });
                                document.querySelectorAll('tr:not(.has-apology) .apology-status').forEach(el => {
                                    el.textContent = 'No Apology';
                                    el.className = 'apology-status apology-none';
                                });
                            })
                            .catch(error => console.error('Error fetching apologies:', error));
                    }
                })
                .catch(error => console.error('Error checking attendance:', error));
        }
    </script>
</body>
</html>
<?php
$conn->close();
?>