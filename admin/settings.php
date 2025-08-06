<?php
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Check if user is admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Initialize variables
$error = '';
$success = '';
$settings = [];
$groups = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update settings
    if (isset($_POST['update_settings'])) {
        try {
            $db->beginTransaction();
            
            foreach ($_POST['settings'] as $setting_id => $setting_value) {
                $stmt = $db->prepare("UPDATE system_settings 
                                     SET setting_value = ?, updated_at = NOW() 
                                     WHERE setting_id = ?");
                $stmt->execute([trim($setting_value), $setting_id]);
            }
            
            $db->commit();
            $success = 'Settings updated successfully!';
        } catch (PDOException $e) {
            $db->rollBack();
            $error = 'Error updating settings: ' . $e->getMessage();
        }
    }
    
    // Add new setting
    if (isset($_POST['add_setting'])) {
        $key = trim($_POST['new_key']);
        $value = trim($_POST['new_value']);
        $group = trim($_POST['new_group']);
        $is_public = isset($_POST['new_is_public']) ? 1 : 0;
        
        if (empty($key)) {
            $error = 'Setting key is required';
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO system_settings 
                                    (setting_key, setting_value, setting_group, is_public) 
                                    VALUES (?, ?, ?, ?)");
                $stmt->execute([$key, $value, $group, $is_public]);
                $success = 'New setting added successfully!';
            } catch (PDOException $e) {
                $error = 'Error adding setting: ' . $e->getMessage();
            }
        }
    }
}

// Get all settings grouped by setting_group
try {
    $stmt = $db->query("SELECT * FROM system_settings ORDER BY setting_group, setting_key");
    $all_settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group settings by their group
    foreach ($all_settings as $setting) {
        $group = $setting['setting_group'] ?: 'General';
        $settings[$group][] = $setting;
    }
    
    // Get distinct groups for dropdown
    $stmt = $db->query("SELECT DISTINCT setting_group FROM system_settings WHERE setting_group IS NOT NULL ORDER BY setting_group");
    $groups = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $error = 'Error fetching settings: ' . $e->getMessage();
}

// Include header
include '../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">System Settings</h1>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-8">
                    <form method="post">
                        <?php foreach ($settings as $group => $group_settings): ?>
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5><?php echo htmlspecialchars($group); ?></h5>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($group_settings as $setting): ?>
                                        <div class="mb-3">
                                            <label for="setting_<?php echo $setting['setting_id']; ?>" class="form-label">
                                                <?php echo htmlspecialchars($setting['setting_key']); ?>
                                                <?php if ($setting['is_public']): ?>
                                                    <span class="badge bg-info">Public</span>
                                                <?php endif; ?>
                                            </label>
                                            <textarea class="form-control" id="setting_<?php echo $setting['setting_id']; ?>" 
                                                      name="settings[<?php echo $setting['setting_id']; ?>]" 
                                                      rows="<?php echo min(5, max(1, substr_count($setting['setting_value'], "\n") + 1)); ?>"><?php 
                                                      echo htmlspecialchars($setting['setting_value']); 
                                                  ?></textarea>
                                            <?php if ($setting['description']): ?>
                                                <div class="form-text"><?php echo htmlspecialchars($setting['description']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="text-end">
                            <button type="submit" name="update_settings" class="btn btn-primary">Save Settings</button>
                        </div>
                    </form>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5>Add New Setting</h5>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <div class="mb-3">
                                    <label for="new_key" class="form-label">Setting Key</label>
                                    <input type="text" class="form-control" id="new_key" name="new_key" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_value" class="form-label">Setting Value</label>
                                    <textarea class="form-control" id="new_value" name="new_value" rows="3"></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_group" class="form-label">Group</label>
                                    <input type="text" class="form-control" id="new_group" name="new_group" list="groups">
                                    <datalist id="groups">
                                        <?php foreach ($groups as $group): ?>
                                            <option value="<?php echo htmlspecialchars($group); ?>">
                                        <?php endforeach; ?>
                                    </datalist>
                                </div>
                                
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="new_is_public" name="new_is_public">
                                    <label class="form-check-label" for="new_is_public">Public Setting</label>
                                </div>
                                
                                <button type="submit" name="add_setting" class="btn btn-success">Add Setting</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>