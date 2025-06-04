<?php
/**
 * Claim.MD API Integration for American Caregivers Inc
 * 
 * This class handles all interactions with the Claim.MD clearinghouse API
 * including claim submission, status checking, ERA retrieval, and eligibility verification
 */

class ClaimMDAPI {
    private $accountKey;
    private $apiUrl;
    private $debug;
    
    public function __construct($accountKey = null, $testMode = false) {
        $this->accountKey = $accountKey ?: CLAIMMD_ACCOUNT_KEY;
        $this->apiUrl = $testMode ? 'https://svc.claim.md/test/services/' : 'https://svc.claim.md/services/';
        $this->debug = APP_ENV === 'development';
    }
    
    /**
     * Submit claims to Claim.MD
     * Supports 837P format or structured array data
     */
    public function submitClaims($claimsData, $format = '837P') {
        if ($format === '837P') {
            // Submit as X12 837P file
            return $this->uploadBatchFile($claimsData, 'sample.txt');
        } else {
            // Convert array to supported format (JSON/XML)
            $jsonData = $this->convertToClaimMDFormat($claimsData);
            return $this->uploadBatchFile($jsonData, 'claims.json');
        }
    }
    
    /**
     * Upload batch file to Claim.MD
     */
    private function uploadBatchFile($fileData, $filename) {
        $endpoint = $this->apiUrl . 'upload/';
        
        // Create temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'claimmd_');
        file_put_contents($tempFile, $fileData);
        
        $postData = [
            'AccountKey' => $this->accountKey,
            'File' => new CURLFile($tempFile, 'application/octet-stream', $filename),
            'Filename' => $filename
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $endpoint,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Accept: application/json']
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Clean up temp file
        unlink($tempFile);
        
        if ($httpCode !== 200) {
            throw new Exception("Claim.MD API error: HTTP $httpCode");
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Get claim status updates
     */
    public function getClaimStatus($responseId = '0') {
        $endpoint = $this->apiUrl . 'response/';
        
        $postData = [
            'AccountKey' => $this->accountKey,
            'ResponseID' => $responseId
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $endpoint,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($postData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/x-www-form-urlencoded'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("Claim.MD API error: HTTP $httpCode");
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Check patient eligibility
     */
    public function checkEligibility($patientData, $serviceDate, $serviceCode = '30') {
        $endpoint = $this->apiUrl . 'eligdata/';
        
        $postData = array_merge([
            'AccountKey' => $this->accountKey,
            'service_code' => $serviceCode,
            'fdos' => date('Ymd', strtotime($serviceDate)),
            'prov_npi' => ORGANIZATION_NPI,
            'prov_taxid' => ORGANIZATION_TAX_ID,
            'pat_rel' => '18' // Self by default
        ], $patientData);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $endpoint,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($postData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/x-www-form-urlencoded'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("Claim.MD API error: HTTP $httpCode");
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Get list of ERAs (Electronic Remittance Advice)
     */
    public function getERAList($lastERAId = null, $newOnly = true) {
        $endpoint = $this->apiUrl . 'eralist/';
        
        $postData = [
            'AccountKey' => $this->accountKey,
            'NewOnly' => $newOnly ? '1' : '0'
        ];
        
        if ($lastERAId) {
            $postData['ERAID'] = $lastERAId;
        }
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $endpoint,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($postData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/x-www-form-urlencoded'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("Claim.MD API error: HTTP $httpCode");
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Get ERA details in XML/JSON format
     */
    public function getERADetails($eraId) {
        $endpoint = $this->apiUrl . 'eradata/';
        
        $postData = [
            'AccountKey' => $this->accountKey,
            'eraid' => $eraId
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $endpoint,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($postData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/x-www-form-urlencoded'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("Claim.MD API error: HTTP $httpCode");
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Get payer list
     */
    public function getPayerList($payerId = null) {
        $endpoint = $this->apiUrl . 'payerlist/';
        
        $postData = ['AccountKey' => $this->accountKey];
        
        if ($payerId) {
            $postData['payerid'] = $payerId;
        }
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $endpoint,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($postData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/x-www-form-urlencoded'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("Claim.MD API error: HTTP $httpCode");
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Convert internal claim format to Claim.MD JSON format
     */
    private function convertToClaimMDFormat($claims) {
        $claimMDData = ['claims' => []];
        
        foreach ($claims as $claim) {
            $claimMDData['claims'][] = [
                'remote_claimid' => $claim['id'],
                'pcn' => $claim['patient_account'],
                'fdos' => $claim['service_date'],
                'tdos' => $claim['service_date'],
                'total_charge' => $claim['total_amount'],
                
                // Patient info
                'pat_name_l' => $claim['patient_last_name'],
                'pat_name_f' => $claim['patient_first_name'],
                'pat_dob' => $claim['patient_dob'],
                'pat_sex' => $claim['patient_gender'],
                
                // Insurance info
                'ins_name_l' => $claim['insured_last_name'] ?: $claim['patient_last_name'],
                'ins_name_f' => $claim['insured_first_name'] ?: $claim['patient_first_name'],
                'ins_number' => $claim['ma_number'],
                'ins_group' => $claim['group_number'] ?? '',
                'pat_rel' => '18', // Self
                
                // Billing provider
                'bill_npi' => ORGANIZATION_NPI,
                'bill_taxid' => ORGANIZATION_TAX_ID,
                'bill_name' => 'American Caregivers Inc',
                
                // Rendering provider
                'prov_npi' => $claim['provider_npi'],
                'prov_name_l' => $claim['provider_last_name'],
                'prov_name_f' => $claim['provider_first_name'],
                
                // Diagnosis codes
                'diag_1' => $claim['diagnosis_1'],
                'diag_2' => $claim['diagnosis_2'] ?? '',
                
                // Service lines
                'services' => $this->formatServiceLines($claim['services'])
            ];
        }
        
        return json_encode($claimMDData);
    }
    
    /**
     * Format service lines for Claim.MD
     */
    private function formatServiceLines($services) {
        $lines = [];
        $lineNumber = 1;
        
        foreach ($services as $service) {
            $lines[] = [
                'line' => $lineNumber++,
                'fdos' => $service['date'],
                'tdos' => $service['date'],
                'pos' => $service['place_of_service'] ?? '12', // Home
                'proc' => $service['procedure_code'],
                'mod1' => $service['modifier_1'] ?? '',
                'mod2' => $service['modifier_2'] ?? '',
                'units' => $service['units'],
                'charge' => $service['charge'],
                'npi' => $service['provider_npi'] ?? ''
            ];
        }
        
        return $lines;
    }
    
    /**
     * Log API activity for debugging
     */
    private function logActivity($action, $data, $response) {
        if (!$this->debug) return;
        
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'action' => $action,
            'request' => $data,
            'response' => $response
        ];
        
        error_log(json_encode($logEntry) . "\n", 3, LOGS_DIR . '/claimmd_api.log');
    }
}
?>