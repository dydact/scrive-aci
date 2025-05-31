<?php
/**
 * Client Repository - Database access layer for clients
 */

class ClientRepository {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Get all clients with statistics
     */
    public function getAllClients() {
        try {
            $stmt = $this->db->query("
                SELECT c.*, 
                       COUNT(DISTINCT sa.staff_id) as assigned_staff,
                       COUNT(DISTINCT sn.id) as total_sessions,
                       MAX(sn.session_date) as last_session_date
                FROM autism_clients c
                LEFT JOIN autism_staff_assignments sa ON c.id = sa.client_id AND sa.is_active = 1
                LEFT JOIN autism_session_notes sn ON c.id = sn.client_id
                GROUP BY c.id
                ORDER BY c.last_name, c.first_name
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching clients: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get clients assigned to specific staff member
     */
    public function getClientsByStaff($staff_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, 
                       sa.assignment_type,
                       COUNT(DISTINCT sn.id) as total_sessions,
                       MAX(sn.session_date) as last_session_date
                FROM autism_clients c
                INNER JOIN autism_staff_assignments sa ON c.id = sa.client_id
                LEFT JOIN autism_session_notes sn ON c.id = sn.client_id AND sn.staff_id = ?
                WHERE sa.staff_id = ? AND sa.is_active = 1 AND c.status = 'active'
                GROUP BY c.id, sa.assignment_type
                ORDER BY c.last_name, c.first_name
            ");
            $stmt->execute([$staff_id, $staff_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching staff clients: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get single client by ID
     */
    public function getClientById($client_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*,
                       COUNT(DISTINCT sn.id) as total_sessions,
                       COUNT(DISTINCT sa.staff_id) as assigned_staff
                FROM autism_clients c
                LEFT JOIN autism_session_notes sn ON c.id = sn.client_id
                LEFT JOIN autism_staff_assignments sa ON c.id = sa.client_id AND sa.is_active = 1
                WHERE c.id = ?
                GROUP BY c.id
            ");
            $stmt->execute([$client_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching client: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Search clients by name or MA number
     */
    public function searchClients($search_term) {
        try {
            $search = "%$search_term%";
            $stmt = $this->db->prepare("
                SELECT c.*, 
                       COUNT(DISTINCT sa.staff_id) as assigned_staff
                FROM autism_clients c
                LEFT JOIN autism_staff_assignments sa ON c.id = sa.client_id AND sa.is_active = 1
                WHERE c.first_name LIKE ? 
                   OR c.last_name LIKE ? 
                   OR c.ma_number LIKE ?
                   OR CONCAT(c.first_name, ' ', c.last_name) LIKE ?
                GROUP BY c.id
                ORDER BY c.last_name, c.first_name
                LIMIT 50
            ");
            $stmt->execute([$search, $search, $search, $search]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error searching clients: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get client statistics
     */
    public function getClientStats() {
        try {
            $stats = [];
            
            // Total active clients
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM autism_clients WHERE status = 'active'");
            $stats['total_active'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Clients with sessions this month
            $stmt = $this->db->query("
                SELECT COUNT(DISTINCT client_id) as total 
                FROM autism_session_notes 
                WHERE MONTH(session_date) = MONTH(CURRENT_DATE()) 
                  AND YEAR(session_date) = YEAR(CURRENT_DATE())
            ");
            $stats['sessions_this_month'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // New clients this month
            $stmt = $this->db->query("
                SELECT COUNT(*) as total 
                FROM autism_clients 
                WHERE MONTH(enrollment_date) = MONTH(CURRENT_DATE()) 
                  AND YEAR(enrollment_date) = YEAR(CURRENT_DATE())
            ");
            $stats['new_this_month'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            return $stats;
        } catch (PDOException $e) {
            error_log("Error fetching client stats: " . $e->getMessage());
            return ['total_active' => 0, 'sessions_this_month' => 0, 'new_this_month' => 0];
        }
    }
    
    /**
     * Add new client
     */
    public function addClient($data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO autism_clients (
                    first_name, last_name, date_of_birth, gender, ma_number,
                    address, city, state, zip, phone, email,
                    emergency_contact_name, emergency_contact_phone, emergency_contact_relationship,
                    status, enrollment_date
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['first_name'],
                $data['last_name'],
                $data['date_of_birth'],
                $data['gender'] ?? 'other',
                $data['ma_number'],
                $data['address'] ?? null,
                $data['city'] ?? null,
                $data['state'] ?? 'MD',
                $data['zip'] ?? null,
                $data['phone'] ?? null,
                $data['email'] ?? null,
                $data['emergency_contact_name'] ?? null,
                $data['emergency_contact_phone'] ?? null,
                $data['emergency_contact_relationship'] ?? null,
                $data['status'] ?? 'active',
                $data['enrollment_date'] ?? date('Y-m-d')
            ]);
            
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error adding client: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update client
     */
    public function updateClient($client_id, $data) {
        try {
            $fields = [];
            $values = [];
            
            // Build dynamic update query
            foreach ($data as $field => $value) {
                if (in_array($field, ['first_name', 'last_name', 'date_of_birth', 'gender', 
                    'ma_number', 'address', 'city', 'state', 'zip', 'phone', 'email',
                    'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relationship',
                    'status', 'discharge_date'])) {
                    $fields[] = "$field = ?";
                    $values[] = $value;
                }
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $values[] = $client_id;
            $sql = "UPDATE autism_clients SET " . implode(', ', $fields) . " WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($values);
        } catch (PDOException $e) {
            error_log("Error updating client: " . $e->getMessage());
            return false;
        }
    }
}