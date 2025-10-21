<?php
/**
 * Archive System Helper Functions
 * Add these functions to your config.php or create a new file: admin/includes/archive_functions.php
 */

/**
 * Archive a record (soft delete)
 * 
 * @param PDO $conn Database connection
 * @param string $table Table name
 * @param string $id_column Primary key column name
 * @param int $id Record ID to archive
 * @param int $user_id User performing the action
 * @return bool Success status
 */
function archiveRecord($conn, $table, $id_column, $id, $user_id = null) {
    try {
        $conn->beginTransaction();
        
        // Update the record to set is_archived = 1
        $sql = "UPDATE {$table} 
                SET is_archived = 1, 
                    archived_at = NOW(), 
                    archived_by = :user_id 
                WHERE {$id_column} = :id";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':user_id' => $user_id ?? $_SESSION['user_id'] ?? null,
            ':id' => $id
        ]);
        
        // Log the archive action
        logArchiveAction($conn, $table, $id, 'archived', $user_id);
        
        $conn->commit();
        return true;
        
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log("Archive error: " . $e->getMessage());
        return false;
    }
}

/**
 * Restore an archived record
 * 
 * @param PDO $conn Database connection
 * @param string $table Table name
 * @param string $id_column Primary key column name
 * @param int $id Record ID to restore
 * @param int $user_id User performing the action
 * @return bool Success status
 */
function restoreRecord($conn, $table, $id_column, $id, $user_id = null) {
    try {
        $conn->beginTransaction();
        
        // Update the record to set is_archived = 0
        $sql = "UPDATE {$table} 
                SET is_archived = 0, 
                    archived_at = NULL, 
                    archived_by = NULL 
                WHERE {$id_column} = :id";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        // Log the restore action
        logArchiveAction($conn, $table, $id, 'restored', $user_id);
        
        $conn->commit();
        return true;
        
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log("Restore error: " . $e->getMessage());
        return false;
    }
}

/**
 * Permanently delete a record
 * 
 * @param PDO $conn Database connection
 * @param string $table Table name
 * @param string $id_column Primary key column name
 * @param int $id Record ID to delete
 * @param int $user_id User performing the action
 * @return bool Success status
 */
function permanentlyDeleteRecord($conn, $table, $id_column, $id, $user_id = null) {
    try {
        $conn->beginTransaction();
        
        // Log before deletion
        logArchiveAction($conn, $table, $id, 'permanently_deleted', $user_id);
        
        // Delete the record
        $sql = "DELETE FROM {$table} WHERE {$id_column} = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        $conn->commit();
        return true;
        
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log("Permanent delete error: " . $e->getMessage());
        return false;
    }
}

/**
 * Log archive action to archive_logs table
 * 
 * @param PDO $conn Database connection
 * @param string $table Table name
 * @param int $record_id Record ID
 * @param string $action Action performed (archived, restored, permanently_deleted)
 * @param int $user_id User performing the action
 * @param string $notes Optional notes
 */
function logArchiveAction($conn, $table, $record_id, $action, $user_id = null, $notes = null) {
    try {
        $sql = "INSERT INTO archive_logs (table_name, record_id, action, performed_by, notes) 
                VALUES (:table, :record_id, :action, :user_id, :notes)";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':table' => $table,
            ':record_id' => $record_id,
            ':action' => $action,
            ':user_id' => $user_id ?? $_SESSION['user_id'] ?? null,
            ':notes' => $notes
        ]);
    } catch (PDOException $e) {
        error_log("Archive log error: " . $e->getMessage());
    }
}

/**
 * Get archived items count
 * 
 * @param PDO $conn Database connection
 * @param string $table Table name (optional, null for all tables)
 * @return int|array Count or array of counts per table
 */
function getArchivedCount($conn, $table = null) {
    try {
        if ($table) {
            $sql = "SELECT COUNT(*) as count FROM {$table} WHERE is_archived = 1";
            $stmt = $conn->query($sql);
            return (int) $stmt->fetch()['count'];
        } else {
            // Get counts for all tables
            $stmt = $conn->query("SELECT * FROM archived_items_summary");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        error_log("Get archived count error: " . $e->getMessage());
        return $table ? 0 : [];
    }
}

/**
 * Get archived items from a table
 * 
 * @param PDO $conn Database connection
 * @param string $table Table name
 * @param int $limit Limit results (optional)
 * @param int $offset Offset for pagination (optional)
 * @return array Archived records
 */
function getArchivedItems($conn, $table, $limit = 50, $offset = 0) {
    try {
        $sql = "SELECT * FROM {$table} 
                WHERE is_archived = 1 
                ORDER BY archived_at DESC 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Get archived items error: " . $e->getMessage());
        return [];
    }
}

/**
 * Bulk archive multiple records
 * 
 * @param PDO $conn Database connection
 * @param string $table Table name
 * @param string $id_column Primary key column name
 * @param array $ids Array of record IDs to archive
 * @param int $user_id User performing the action
 * @return bool Success status
 */
function bulkArchive($conn, $table, $id_column, $ids, $user_id = null) {
    try {
        $conn->beginTransaction();
        
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        
        $sql = "UPDATE {$table} 
                SET is_archived = 1, 
                    archived_at = NOW(), 
                    archived_by = ? 
                WHERE {$id_column} IN ({$placeholders})";
        
        $stmt = $conn->prepare($sql);
        $params = array_merge([$user_id ?? $_SESSION['user_id'] ?? null], $ids);
        $stmt->execute($params);
        
        // Log each archive action
        foreach ($ids as $id) {
            logArchiveAction($conn, $table, $id, 'archived', $user_id);
        }
        
        $conn->commit();
        return true;
        
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log("Bulk archive error: " . $e->getMessage());
        return false;
    }
}

/**
 * Auto-archive old records based on criteria
 * 
 * @param PDO $conn Database connection
 * @param string $table Table name
 * @param string $date_column Column to check for age
 * @param int $days_old Records older than this many days will be archived
 * @param int $user_id User performing the action
 * @return int Number of records archived
 */
function autoArchiveOldRecords($conn, $table, $date_column, $days_old = 365, $user_id = null) {
    try {
        $sql = "UPDATE {$table} 
                SET is_archived = 1, 
                    archived_at = NOW(), 
                    archived_by = :user_id 
                WHERE is_archived = 0 
                AND {$date_column} < DATE_SUB(NOW(), INTERVAL :days DAY)";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':user_id' => $user_id ?? $_SESSION['user_id'] ?? null,
            ':days' => $days_old
        ]);
        
        return $stmt->rowCount();
        
    } catch (PDOException $e) {
        error_log("Auto-archive error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get archive statistics
 * 
 * @param PDO $conn Database connection
 * @return array Statistics array
 */
function getArchiveStatistics($conn) {
    try {
        $stats = [
            'total_archived' => 0,
            'by_table' => [],
            'recent_archives' => [],
            'top_archivers' => []
        ];
        
        // Total archived items
        $stmt = $conn->query("
            SELECT SUM(archived_count) as total 
            FROM archived_items_summary
        ");
        $stats['total_archived'] = (int) $stmt->fetch()['total'];
        
        // Archived by table
        $stmt = $conn->query("SELECT * FROM archived_items_summary ORDER BY archived_count DESC");
        $stats['by_table'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Recent archive actions
        $stmt = $conn->query("
            SELECT al.*, u.username 
            FROM archive_logs al 
            LEFT JOIN users u ON al.performed_by = u.user_id 
            WHERE al.action = 'archived'
            ORDER BY al.performed_at DESC 
            LIMIT 10
        ");
        $stats['recent_archives'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Top users who archive
        $stmt = $conn->query("
            SELECT u.username, COUNT(*) as archive_count 
            FROM archive_logs al 
            JOIN users u ON al.performed_by = u.user_id 
            WHERE al.action = 'archived'
            GROUP BY u.user_id 
            ORDER BY archive_count DESC 
            LIMIT 5
        ");
        $stats['top_archivers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $stats;
        
    } catch (PDOException $e) {
        error_log("Get archive statistics error: " . $e->getMessage());
        return [];
    }
}

/**
 * Check if a record is archived
 * 
 * @param PDO $conn Database connection
 * @param string $table Table name
 * @param string $id_column Primary key column name
 * @param int $id Record ID
 * @return bool True if archived, false otherwise
 */
function isArchived($conn, $table, $id_column, $id) {
    try {
        $sql = "SELECT is_archived FROM {$table} WHERE {$id_column} = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        
        return $result && $result['is_archived'] == 1;
        
    } catch (PDOException $e) {
        error_log("Check archived error: " . $e->getMessage());
        return false;
    }
}
?>