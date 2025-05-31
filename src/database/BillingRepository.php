<?php
/**
 * Billing Repository - Database access layer for billing
 */

class BillingRepository {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Get billing dashboard statistics
     */
    public function getDashboardStats() {
        try {
            $stats = [];
            
            // Total claims this month
            $stmt = $this->db->query("
                SELECT COUNT(*) as total, SUM(total_amount) as amount 
                FROM autism_claims 
                WHERE MONTH(service_date_from) = MONTH(CURRENT_DATE()) 
                  AND YEAR(service_date_from) = YEAR(CURRENT_DATE())
            ");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['claims_this_month'] = $result['total'] ?? 0;
            $stats['revenue_this_month'] = $result['amount'] ?? 0;
            
            // Pending claims
            $stmt = $this->db->query("
                SELECT COUNT(*) as total, SUM(total_amount) as amount 
                FROM autism_claims 
                WHERE status IN ('draft', 'generated', 'submitted')
            ");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['pending_claims'] = $result['total'] ?? 0;
            $stats['pending_amount'] = $result['amount'] ?? 0;
            
            // Paid claims this month
            $stmt = $this->db->query("
                SELECT COUNT(*) as total, SUM(payment_amount) as amount 
                FROM autism_claims 
                WHERE status = 'paid' 
                  AND MONTH(payment_date) = MONTH(CURRENT_DATE())
                  AND YEAR(payment_date) = YEAR(CURRENT_DATE())
            ");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['paid_this_month'] = $result['total'] ?? 0;
            $stats['collected_this_month'] = $result['amount'] ?? 0;
            
            // Outstanding receivables
            $stmt = $this->db->query("
                SELECT SUM(total_amount - COALESCE(payment_amount, 0)) as amount 
                FROM autism_claims 
                WHERE status IN ('submitted', 'accepted')
            ");
            $stats['outstanding_receivables'] = $stmt->fetch(PDO::FETCH_ASSOC)['amount'] ?? 0;
            
            return $stats;
        } catch (PDOException $e) {
            error_log("Error fetching billing stats: " . $e->getMessage());
            return [
                'claims_this_month' => 0,
                'revenue_this_month' => 0,
                'pending_claims' => 0,
                'pending_amount' => 0,
                'paid_this_month' => 0,
                'collected_this_month' => 0,
                'outstanding_receivables' => 0
            ];
        }
    }
    
    /**
     * Get recent claims
     */
    public function getRecentClaims($limit = 10) {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, cl.first_name, cl.last_name, cl.ma_number
                FROM autism_claims c
                JOIN autism_clients cl ON c.client_id = cl.id
                ORDER BY c.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching recent claims: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get unbilled sessions
     */
    public function getUnbilledSessions() {
        try {
            $stmt = $this->db->query("
                SELECT 
                    sn.client_id,
                    c.first_name,
                    c.last_name,
                    c.ma_number,
                    COUNT(sn.id) as session_count,
                    MIN(sn.session_date) as date_from,
                    MAX(sn.session_date) as date_to,
                    SUM(TIME_TO_SEC(TIMEDIFF(sn.end_time, sn.start_time))/3600) as total_hours
                FROM autism_session_notes sn
                JOIN autism_clients c ON sn.client_id = c.id
                LEFT JOIN autism_claim_lines cl ON sn.id = cl.session_note_id
                WHERE cl.id IS NULL 
                  AND sn.status = 'approved'
                  AND c.status = 'active'
                GROUP BY sn.client_id, c.first_name, c.last_name, c.ma_number
                HAVING session_count > 0
                ORDER BY c.last_name, c.first_name
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching unbilled sessions: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Create billing claim
     */
    public function createClaim($client_id, $date_from, $date_to) {
        try {
            $this->db->beginTransaction();
            
            // Generate claim number
            $claim_number = 'CLM' . date('Ymd') . sprintf('%04d', rand(1, 9999));
            
            // Get client info
            $stmt = $this->db->prepare("SELECT * FROM autism_clients WHERE id = ?");
            $stmt->execute([$client_id]);
            $client = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$client) {
                throw new Exception("Client not found");
            }
            
            // Get unbilled sessions
            $stmt = $this->db->prepare("
                SELECT sn.*, st.billing_code, st.rate, st.unit_type
                FROM autism_session_notes sn
                JOIN autism_service_types st ON sn.service_type_id = st.id
                LEFT JOIN autism_claim_lines cl ON sn.id = cl.session_note_id
                WHERE sn.client_id = ? 
                  AND sn.session_date BETWEEN ? AND ?
                  AND cl.id IS NULL
                  AND sn.status = 'approved'
            ");
            $stmt->execute([$client_id, $date_from, $date_to]);
            $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($sessions)) {
                throw new Exception("No unbilled sessions found");
            }
            
            // Calculate total amount
            $total_amount = 0;
            foreach ($sessions as $session) {
                $duration_hours = (strtotime($session['end_time']) - strtotime($session['start_time'])) / 3600;
                $units = $session['unit_type'] === '15min' ? $duration_hours * 4 : $duration_hours;
                $amount = $units * $session['rate'];
                $total_amount += $amount;
            }
            
            // Create claim
            $stmt = $this->db->prepare("
                INSERT INTO autism_claims (
                    claim_number, client_id, service_date_from, service_date_to, 
                    total_amount, status, created_at
                ) VALUES (?, ?, ?, ?, ?, 'draft', NOW())
            ");
            $stmt->execute([$claim_number, $client_id, $date_from, $date_to, $total_amount]);
            $claim_id = $this->db->lastInsertId();
            
            // Create claim lines
            foreach ($sessions as $session) {
                $duration_hours = (strtotime($session['end_time']) - strtotime($session['start_time'])) / 3600;
                $units = $session['unit_type'] === '15min' ? $duration_hours * 4 : $duration_hours;
                $amount = $units * $session['rate'];
                
                $stmt = $this->db->prepare("
                    INSERT INTO autism_claim_lines (
                        claim_id, session_note_id, service_type_id, service_date,
                        units, rate, amount
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $claim_id, 
                    $session['id'], 
                    $session['service_type_id'],
                    $session['session_date'],
                    $units,
                    $session['rate'],
                    $amount
                ]);
            }
            
            $this->db->commit();
            return $claim_id;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error creating claim: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get service type statistics
     */
    public function getServiceTypeStats() {
        try {
            $stmt = $this->db->query("
                SELECT 
                    st.service_name,
                    st.service_code,
                    COUNT(DISTINCT sn.id) as session_count,
                    SUM(TIME_TO_SEC(TIMEDIFF(sn.end_time, sn.start_time))/3600) as total_hours,
                    COUNT(DISTINCT sn.client_id) as unique_clients
                FROM autism_session_notes sn
                JOIN autism_service_types st ON sn.service_type_id = st.id
                WHERE MONTH(sn.session_date) = MONTH(CURRENT_DATE())
                  AND YEAR(sn.session_date) = YEAR(CURRENT_DATE())
                GROUP BY st.id, st.service_name, st.service_code
                ORDER BY session_count DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching service type stats: " . $e->getMessage());
            return [];
        }
    }
}