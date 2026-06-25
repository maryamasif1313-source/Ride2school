<?php
// =============================================
//  db.php — Database Connection File
//  Ride2School Project
// =============================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // XAMPP default
define('DB_PASS', '');           // XAMPP default (no password)
define('DB_NAME', 'ride2school_db');

// Create connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (!$conn) {
    die("
        <div style='font-family:sans-serif; background:#fff0f0; 
                    border:1px solid #ffaaaa; padding:20px; 
                    border-radius:8px; margin:20px;'>
            <h3 style='color:#cc0000;'>❌ Database Connection Failed</h3>
            <p>" . mysqli_connect_error() . "</p>
            <p><strong>Fix:</strong> Make sure XAMPP Apache &amp; MySQL are running.</p>
        </div>
    ");
}

// Set charset to UTF-8 (for Urdu/special characters support)
mysqli_set_charset($conn, "utf8");

// =============================================
//  LINKED LIST FUNCTIONS (Driver Storage)
// =============================================

// Add driver to linked list (update next_node pointers)
function linkedListAddDriver($conn, $new_driver_id) {
    // Find the last node (driver with next_node = NULL)
    $result = mysqli_query($conn, 
        "SELECT id FROM drivers WHERE next_node IS NULL AND id != $new_driver_id ORDER BY id DESC LIMIT 1"
    );
    if ($row = mysqli_fetch_assoc($result)) {
        $last_id = $row['id'];
        mysqli_query($conn, 
            "UPDATE drivers SET next_node = $new_driver_id WHERE id = $last_id"
        );
    }
}

// Traverse linked list — returns all drivers in linked order
function linkedListTraverse($conn) {
    $drivers = [];
    // Find head (first registered driver)
    $result = mysqli_query($conn, "SELECT * FROM drivers ORDER BY id ASC LIMIT 1");
    if (!$result || mysqli_num_rows($result) == 0) return $drivers;
    
    $current = mysqli_fetch_assoc($result);
    while ($current) {
        $drivers[] = $current;
        if ($current['next_node']) {
            $id = $current['next_node'];
            $r  = mysqli_query($conn, "SELECT * FROM drivers WHERE id = $id");
            $current = mysqli_fetch_assoc($r);
        } else {
            $current = null;
        }
    }
    return $drivers;
}

// =============================================
//  QUEUE FUNCTIONS (Registration Order)
// =============================================

// Enqueue — add user to registration queue
function enqueue($conn, $user_id, $role) {
    mysqli_query($conn, 
        "INSERT INTO registration_queue (user_id, role) VALUES ($user_id, '$role')"
    );
}

// Dequeue — process next pending registration
function dequeue($conn) {
    $result = mysqli_query($conn, 
        "SELECT * FROM registration_queue WHERE status='pending' ORDER BY queue_id ASC LIMIT 1"
    );
    if ($row = mysqli_fetch_assoc($result)) {
        mysqli_query($conn, 
            "UPDATE registration_queue SET status='processed' WHERE queue_id=" . $row['queue_id']
        );
        return $row;
    }
    return null;
}

// =============================================
//  STACK FUNCTIONS (Undo / Action History)
// =============================================

// Push action to stack
function stackPush($conn, $driver_id, $action_type, $old_data = null) {
    $old_json = $old_data ? "'" . mysqli_real_escape_string($conn, json_encode($old_data)) . "'" : "NULL";
    mysqli_query($conn, 
        "INSERT INTO action_stack (driver_id, action_type, old_data) 
         VALUES ($driver_id, '$action_type', $old_json)"
    );
}

// Pop — get last action from stack (for undo)
function stackPop($conn, $driver_id) {
    $result = mysqli_query($conn, 
        "SELECT * FROM action_stack WHERE driver_id=$driver_id 
         ORDER BY stack_id DESC LIMIT 1"
    );
    if ($row = mysqli_fetch_assoc($result)) {
        mysqli_query($conn, 
            "DELETE FROM action_stack WHERE stack_id=" . $row['stack_id']
        );
        return $row;
    }
    return null;
}

// =============================================
//  SEARCH FUNCTION (Linear Search on drivers)
// =============================================

function searchDrivers($conn, $school, $area, $vehicle_type, $children_count) {
    $school       = mysqli_real_escape_string($conn, $school);
    $area         = mysqli_real_escape_string($conn, $area);
    $vehicle_type = mysqli_real_escape_string($conn, $vehicle_type);
    $children_count = (int)$children_count;

    $query = "SELECT * FROM drivers 
              WHERE schools LIKE '%$school%'
              AND   areas   LIKE '%$area%'
              AND   available_seats >= $children_count";

    if ($vehicle_type !== 'any') {
        $query .= " AND vehicle_type = '$vehicle_type'";
    }

    $result  = mysqli_query($conn, $query);
    $drivers = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $drivers[] = $row;
    }
    return $drivers;
}

// =============================================
//  SORTING FUNCTIONS
// =============================================

// Bubble Sort — sort drivers array by monthly_fare (ascending)
function bubbleSortByFare(&$drivers) {
    $n = count($drivers);
    for ($i = 0; $i < $n - 1; $i++) {
        for ($j = 0; $j < $n - $i - 1; $j++) {
            if ($drivers[$j]['monthly_fare'] > $drivers[$j+1]['monthly_fare']) {
                $temp          = $drivers[$j];
                $drivers[$j]   = $drivers[$j+1];
                $drivers[$j+1] = $temp;
            }
        }
    }
}

// Sort by available seats (descending — most seats first)
function sortByAvailableSeats(&$drivers) {
    $n = count($drivers);
    for ($i = 0; $i < $n - 1; $i++) {
        for ($j = 0; $j < $n - $i - 1; $j++) {
            if ($drivers[$j]['available_seats'] < $drivers[$j+1]['available_seats']) {
                $temp          = $drivers[$j];
                $drivers[$j]   = $drivers[$j+1];
                $drivers[$j+1] = $temp;
            }
        }
    }
}

?>
