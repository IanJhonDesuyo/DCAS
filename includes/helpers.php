<?php
// Shared helpers for reading CSVs, profiling, and cleaning.

function dcas_uploads_dir() {
    $d = __DIR__ . '/../uploads';
    if (!is_dir($d)) @mkdir($d, 0775, true);
    return $d;
}

// Read a CSV file into [headers, rows].
function read_csv($path, $limit = 0) {
    $rows = [];
    $headers = [];
    if (($h = fopen($path, 'r')) !== false) {
        $headers = fgetcsv($h);
        if (!$headers) { fclose($h); return [[], []]; }
        $headers = array_map(fn($x) => trim((string)$x), $headers);
        $i = 0;
        while (($row = fgetcsv($h)) !== false) {
            // pad/truncate to header length
            if (count($row) < count($headers)) {
                $row = array_pad($row, count($headers), '');
            } elseif (count($row) > count($headers)) {
                $row = array_slice($row, 0, count($headers));
            }
            $rows[] = $row;
            $i++;
            if ($limit && $i >= $limit) break;
        }
        fclose($h);
    }
    return [$headers, $rows];
}

function write_csv($path, $headers, $rows) {
    $h = fopen($path, 'w');
    fputcsv($h, $headers);
    foreach ($rows as $r) fputcsv($h, $r);
    fclose($h);
}

function is_missing($v) {
    if ($v === null) return true;
    $s = trim((string)$v);
    if ($s === '') return true;
    $low = strtolower($s);
    return in_array($low, ['na','n/a','null','none','-','--']);
}

function detect_type($values) {
    $intC = $floatC = $dateC = $boolC = $total = 0;
    foreach ($values as $v) {
        if (is_missing($v)) continue;
        $total++;
        $s = trim((string)$v);
        if (preg_match('/^-?\d+$/', $s)) { $intC++; continue; }
        if (is_numeric($s)) { $floatC++; continue; }
        if (in_array(strtolower($s), ['true','false','yes','no'])) { $boolC++; continue; }
        $ts = strtotime($s);
        if ($ts !== false && preg_match('/[\/\-:]/', $s)) { $dateC++; continue; }
    }
    if ($total === 0) return 'string';
    if ($intC / $total > 0.8) return 'integer';
    if (($intC + $floatC) / $total > 0.8) return 'float';
    if ($dateC / $total > 0.7) return 'date';
    if ($boolC / $total > 0.8) return 'boolean';
    return 'string';
}

function profile_dataset($headers, $rows) {
    $cols = count($headers);
    $out = [];
    for ($c = 0; $c < $cols; $c++) {
        $values = array_column($rows, $c);
        $missing = 0; $clean = [];
        foreach ($values as $v) {
            if (is_missing($v)) $missing++;
            else $clean[] = trim((string)$v);
        }
        $type = detect_type($values);
        $unique = count(array_unique($clean));
        $freq = array_count_values($clean);
        arsort($freq);
        $most = $freq ? array_key_first($freq) : null;

        $min = $max = $avg = null;
        if (in_array($type, ['integer','float'])) {
            $nums = array_map('floatval', array_filter($clean, 'is_numeric'));
            if ($nums) {
                $min = min($nums);
                $max = max($nums);
                $avg = round(array_sum($nums) / count($nums), 4);
            }
        } elseif ($type === 'date') {
            $ts = array_filter(array_map('strtotime', $clean));
            if ($ts) { $min = date('Y-m-d', min($ts)); $max = date('Y-m-d', max($ts)); }
        } else {
            if ($clean) { sort($clean); $min = $clean[0]; $max = end($clean); }
        }
        $out[] = [
            'name' => $headers[$c],
            'type' => $type,
            'missing' => $missing,
            'unique' => $unique,
            'most_frequent' => $most,
            'min' => $min, 'max' => $max, 'avg' => $avg
        ];
    }
    return $out;
}

function count_duplicate_rows($rows) {
    $seen = []; $dups = 0;
    foreach ($rows as $r) {
        $k = implode('||', $r);
        if (isset($seen[$k])) $dups++;
        else $seen[$k] = true;
    }
    return $dups;
}

function get_file_or_redirect($conn, $id) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare('SELECT * FROM uploaded_files WHERE id=? AND user_id=?');
    $stmt->bind_param('ii', $id, $user_id);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    if (!$r) { header('Location: index.php'); exit; }
    return $r;
}

function log_action($conn, $file_id, $action, $desc) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare('INSERT INTO action_history (user_id, file_id, action, description) VALUES (?,?,?,?)');
    $stmt->bind_param('iiss', $user_id, $file_id, $action, $desc);
    $stmt->execute();
}

// Workflow validation: ensure proper step sequence
function check_workflow_step($conn, $required_step) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    
    $file_id = $_SESSION['file_id'] ?? 0;
    if (!$file_id) {
        $_SESSION['workflow_message'] = '❌ You must <strong>Upload</strong> a dataset first.';
        header('Location: upload.php');
        exit;
    }
    
    // Check if file exists
    $stmt = $conn->prepare('SELECT id, cleaned_name FROM uploaded_files WHERE id=?');
    $stmt->bind_param('i', $file_id);
    $stmt->execute();
    $file = $stmt->get_result()->fetch_assoc();
    
    if (!$file) {
        $_SESSION['workflow_message'] = '❌ Dataset not found. Please upload again.';
        header('Location: upload.php');
        exit;
    }
    
    // Step 2 (Profile): Just need file to exist
    if ($required_step === 'profile') {
        return;
    }
    
    // Step 3 (Clean): Need profiled data (metadata exists)
    if ($required_step === 'clean') {
        $count = $conn->query("SELECT COUNT(*) c FROM dataset_metadata WHERE file_id=$file_id")->fetch_assoc()['c'];
        if ($count === 0) {
            $_SESSION['workflow_message'] = '⚠️ You must <strong>Profile</strong> your data before cleaning.';
            header('Location: profile.php');
            exit;
        }
        return;
    }
    
    // Step 4 (Analyze): Need cleaned data (cleaning_logs exist)
    if ($required_step === 'analyze') {
        $count = $conn->query("SELECT COUNT(*) c FROM cleaning_logs WHERE file_id=$file_id")->fetch_assoc()['c'];
        if ($count === 0) {
            $_SESSION['workflow_message'] = '⚠️ You must <strong>Clean</strong> your data before analyzing.';
            header('Location: clean.php');
            exit;
        }
        return;
    }
    
    // Step 5 (Dashboard): Need cleaned data
    if ($required_step === 'dashboard') {
        $count = $conn->query("SELECT COUNT(*) c FROM cleaning_logs WHERE file_id=$file_id")->fetch_assoc()['c'];
        if ($count === 0) {
            $_SESSION['workflow_message'] = '⚠️ You must <strong>Clean</strong> your data before visualizing.';
            header('Location: clean.php');
            exit;
        }
        return;
    }
}
