<?php
/**
 * EDI 837 Professional Claim Generator
 * 
 * Generates HIPAA 5010 compliant EDI 837 Professional claims for Maryland Medicaid
 * Specifically designed for autism waiver services
 * 
 * @package     ScriverACI
 * @subpackage  EDI
 * @author      Scriver ACI Development Team
 * @version     1.0.0
 */

namespace ScriverACI\EDI;

class EDI837Generator {
    
    // EDI Delimiters
    const SEGMENT_TERMINATOR = '~';
    const ELEMENT_SEPARATOR = '*';
    const SUBELEMENT_SEPARATOR = ':';
    const REPETITION_SEPARATOR = '^';
    
    // Maryland Medicaid specific values
    const RECEIVER_ID = 'MDMEDICAID';
    const RECEIVER_QUALIFIER = 'ZZ';
    const VERSION_NUMBER = '005010X222A1';
    
    // Autism waiver service codes
    const AUTISM_SERVICE_CODES = [
        'H2019' => 'Therapeutic Behavioral Services',
        'H2014' => 'Skills Training and Development',
        'T1027' => 'Family Training',
        'H2015' => 'Comprehensive Community Support Services',
        'S5111' => 'Home and Community Based Waiver Services'
    ];
    
    private $db;
    private $claims = [];
    private $controlNumber;
    private $interchangeControlNumber;
    private $groupControlNumber;
    private $errors = [];
    private $logger;
    
    /**
     * Constructor
     * 
     * @param PDO $database Database connection
     * @param object $logger Logger instance (optional)
     */
    public function __construct($database, $logger = null) {
        $this->db = $database;
        $this->logger = $logger;
        $this->controlNumber = 1;
        $this->interchangeControlNumber = $this->generateControlNumber();
        $this->groupControlNumber = $this->generateControlNumber();
    }
    
    /**
     * Add a claim to the batch
     * 
     * @param array $claimData Claim data array
     * @return bool Success status
     */
    public function addClaim($claimData) {
        try {
            // Validate required fields
            $validation = $this->validateClaimData($claimData);
            if (!$validation['valid']) {
                $this->errors[] = "Claim validation failed: " . implode(', ', $validation['errors']);
                return false;
            }
            
            // Add claim to batch
            $this->claims[] = $claimData;
            
            $this->log("Claim added successfully: " . $claimData['claim_number']);
            return true;
            
        } catch (\Exception $e) {
            $this->errors[] = "Error adding claim: " . $e->getMessage();
            $this->log("Error adding claim: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Generate complete EDI 837 file content
     * 
     * @param array $submitterInfo Submitter information
     * @return string|false EDI content or false on error
     */
    public function generateEDI($submitterInfo) {
        try {
            if (empty($this->claims)) {
                $this->errors[] = "No claims to process";
                return false;
            }
            
            $edi = '';
            
            // Generate ISA segment (Interchange Control Header)
            $edi .= $this->generateISA($submitterInfo);
            
            // Generate GS segment (Functional Group Header)
            $edi .= $this->generateGS($submitterInfo);
            
            // Generate ST segment (Transaction Set Header)
            $edi .= $this->generateST();
            
            // Generate BHT segment (Beginning of Hierarchical Transaction)
            $edi .= $this->generateBHT($submitterInfo);
            
            // Generate 1000A loop (Submitter)
            $edi .= $this->generate1000A($submitterInfo);
            
            // Generate 1000B loop (Receiver)
            $edi .= $this->generate1000B();
            
            // Process each claim
            $claimCount = 0;
            foreach ($this->claims as $claim) {
                $claimCount++;
                
                // Generate 2000A loop (Billing Provider)
                $edi .= $this->generate2000A($claim, $claimCount);
                
                // Generate 2010AA loop (Billing Provider Name)
                $edi .= $this->generate2010AA($claim);
                
                // Generate 2000B loop (Subscriber)
                $edi .= $this->generate2000B($claim, $claimCount);
                
                // Generate 2010BA loop (Subscriber Name)
                $edi .= $this->generate2010BA($claim);
                
                // Generate 2010BB loop (Payer Name)
                $edi .= $this->generate2010BB($claim);
                
                // Generate 2300 loop (Claim Information)
                $edi .= $this->generate2300($claim);
                
                // Generate 2310A loop (Referring Provider)
                if (!empty($claim['referring_provider'])) {
                    $edi .= $this->generate2310A($claim);
                }
                
                // Generate 2310B loop (Rendering Provider)
                $edi .= $this->generate2310B($claim);
                
                // Generate 2400 loops (Service Lines)
                $edi .= $this->generate2400($claim);
            }
            
            // Generate SE segment (Transaction Set Trailer)
            $segmentCount = substr_count($edi, self::SEGMENT_TERMINATOR) + 1;
            $edi .= $this->generateSE($segmentCount);
            
            // Generate GE segment (Functional Group Trailer)
            $edi .= $this->generateGE();
            
            // Generate IEA segment (Interchange Control Trailer)
            $edi .= $this->generateIEA();
            
            $this->log("EDI 837 generated successfully with " . count($this->claims) . " claims");
            return $edi;
            
        } catch (\Exception $e) {
            $this->errors[] = "Error generating EDI: " . $e->getMessage();
            $this->log("Error generating EDI: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Save EDI file and transaction to database
     * 
     * @param string $ediContent EDI content
     * @param string $filename Output filename
     * @return array Result with file_id and path
     */
    public function saveEDI($ediContent, $filename = null) {
        try {
            if (!$filename) {
                $filename = 'EDI837_' . date('Ymd_His') . '.txt';
            }
            
            // Create EDI files directory if it doesn't exist
            $ediDir = dirname(dirname(__DIR__)) . '/edi_files/outbound';
            if (!is_dir($ediDir)) {
                mkdir($ediDir, 0755, true);
            }
            
            $filepath = $ediDir . '/' . $filename;
            
            // Save file
            if (file_put_contents($filepath, $ediContent) === false) {
                throw new \Exception("Failed to write EDI file");
            }
            
            // Save to database
            $stmt = $this->db->prepare("
                INSERT INTO edi_transactions (
                    transaction_type,
                    transaction_set,
                    interchange_control_number,
                    group_control_number,
                    filename,
                    file_path,
                    claim_count,
                    total_amount,
                    status,
                    created_by,
                    created_at
                ) VALUES (
                    'outbound',
                    '837',
                    :icn,
                    :gcn,
                    :filename,
                    :filepath,
                    :claim_count,
                    :total_amount,
                    'pending',
                    :user_id,
                    NOW()
                )
            ");
            
            $totalAmount = array_sum(array_column($this->claims, 'total_amount'));
            
            $stmt->execute([
                ':icn' => $this->interchangeControlNumber,
                ':gcn' => $this->groupControlNumber,
                ':filename' => $filename,
                ':filepath' => $filepath,
                ':claim_count' => count($this->claims),
                ':total_amount' => $totalAmount,
                ':user_id' => $_SESSION['user_id'] ?? 0
            ]);
            
            $fileId = $this->db->lastInsertId();
            
            // Update claims with EDI file ID
            $this->updateClaimsWithFileId($fileId);
            
            $this->log("EDI file saved successfully: " . $filename);
            
            return [
                'success' => true,
                'file_id' => $fileId,
                'filename' => $filename,
                'filepath' => $filepath,
                'claim_count' => count($this->claims),
                'total_amount' => $totalAmount
            ];
            
        } catch (\Exception $e) {
            $this->errors[] = "Error saving EDI: " . $e->getMessage();
            $this->log("Error saving EDI: " . $e->getMessage(), 'error');
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Generate ISA segment (Interchange Control Header)
     */
    private function generateISA($submitterInfo) {
        $isa = [
            'ISA',
            '00',                                           // Authorization Information Qualifier
            str_pad('', 10),                               // Authorization Information
            '00',                                           // Security Information Qualifier
            str_pad('', 10),                               // Security Information
            self::RECEIVER_QUALIFIER,                      // Interchange ID Qualifier (Sender)
            str_pad($submitterInfo['sender_id'], 15),      // Interchange Sender ID
            self::RECEIVER_QUALIFIER,                      // Interchange ID Qualifier (Receiver)
            str_pad(self::RECEIVER_ID, 15),               // Interchange Receiver ID
            date('ymd'),                                   // Interchange Date
            date('Hi'),                                    // Interchange Time
            '^',                                           // Repetition Separator
            '00501',                                       // Interchange Control Version Number
            str_pad($this->interchangeControlNumber, 9, '0', STR_PAD_LEFT), // Interchange Control Number
            '0',                                           // Acknowledgment Requested
            'P',                                           // Usage Indicator (P = Production)
            ':'                                            // Component Element Separator
        ];
        
        return implode(self::ELEMENT_SEPARATOR, $isa) . self::SEGMENT_TERMINATOR . "\n";
    }
    
    /**
     * Generate GS segment (Functional Group Header)
     */
    private function generateGS($submitterInfo) {
        $gs = [
            'GS',
            'HC',                                          // Functional Identifier Code (Health Care Claim)
            $submitterInfo['sender_id'],                   // Application Sender's Code
            self::RECEIVER_ID,                             // Application Receiver's Code
            date('Ymd'),                                   // Date
            date('Hi'),                                    // Time
            $this->groupControlNumber,                     // Group Control Number
            'X',                                           // Responsible Agency Code
            self::VERSION_NUMBER                           // Version/Release/Industry Identifier Code
        ];
        
        return implode(self::ELEMENT_SEPARATOR, $gs) . self::SEGMENT_TERMINATOR . "\n";
    }
    
    /**
     * Generate ST segment (Transaction Set Header)
     */
    private function generateST() {
        $st = [
            'ST',
            '837',                                         // Transaction Set Identifier Code
            str_pad($this->controlNumber, 4, '0', STR_PAD_LEFT), // Transaction Set Control Number
            self::VERSION_NUMBER                           // Implementation Convention Reference
        ];
        
        return implode(self::ELEMENT_SEPARATOR, $st) . self::SEGMENT_TERMINATOR . "\n";
    }
    
    /**
     * Generate BHT segment (Beginning of Hierarchical Transaction)
     */
    private function generateBHT($submitterInfo) {
        $bht = [
            'BHT',
            '0019',                                        // Hierarchical Structure Code
            '00',                                          // Transaction Set Purpose Code (00 = Original)
            $submitterInfo['batch_id'] ?? date('YmdHis'), // Reference Identification
            date('Ymd'),                                   // Date
            date('Hi'),                                    // Time
            'CH'                                           // Transaction Type Code (CH = Chargeable)
        ];
        
        return implode(self::ELEMENT_SEPARATOR, $bht) . self::SEGMENT_TERMINATOR . "\n";
    }
    
    /**
     * Generate 1000A loop (Submitter Name)
     */
    private function generate1000A($submitterInfo) {
        $edi = '';
        
        // NM1 segment - Submitter Name
        $nm1 = [
            'NM1',
            '41',                                          // Entity Identifier Code (Submitter)
            '2',                                           // Entity Type Qualifier (Non-Person Entity)
            $submitterInfo['organization_name'],           // Name Last or Organization Name
            '',                                            // Name First
            '',                                            // Name Middle
            '',                                            // Name Prefix
            '',                                            // Name Suffix
            '46',                                          // Identification Code Qualifier
            $submitterInfo['submitter_id']                 // Identification Code
        ];
        $edi .= implode(self::ELEMENT_SEPARATOR, $nm1) . self::SEGMENT_TERMINATOR . "\n";
        
        // PER segment - Submitter Contact Information
        $per = [
            'PER',
            'IC',                                          // Contact Function Code
            $submitterInfo['contact_name'],                // Name
            'TE',                                          // Communication Number Qualifier (Telephone)
            $submitterInfo['contact_phone']                // Communication Number
        ];
        
        if (!empty($submitterInfo['contact_email'])) {
            $per[] = 'EM';                                // Email Qualifier
            $per[] = $submitterInfo['contact_email'];     // Email Address
        }
        
        $edi .= implode(self::ELEMENT_SEPARATOR, $per) . self::SEGMENT_TERMINATOR . "\n";
        
        return $edi;
    }
    
    /**
     * Generate 1000B loop (Receiver Name)
     */
    private function generate1000B() {
        $nm1 = [
            'NM1',
            '40',                                          // Entity Identifier Code (Receiver)
            '2',                                           // Entity Type Qualifier
            'MARYLAND MEDICAID',                           // Receiver Name
            '',                                            // Name First
            '',                                            // Name Middle
            '',                                            // Name Prefix
            '',                                            // Name Suffix
            '46',                                          // Identification Code Qualifier
            self::RECEIVER_ID                              // Identification Code
        ];
        
        return implode(self::ELEMENT_SEPARATOR, $nm1) . self::SEGMENT_TERMINATOR . "\n";
    }
    
    /**
     * Generate 2000A loop (Billing Provider Hierarchical Level)
     */
    private function generate2000A($claim, $hierarchicalId) {
        $hl = [
            'HL',
            $hierarchicalId,                               // Hierarchical ID Number
            '',                                            // Hierarchical Parent ID Number
            '20',                                          // Hierarchical Level Code (Information Source)
            '1'                                            // Hierarchical Child Code
        ];
        
        return implode(self::ELEMENT_SEPARATOR, $hl) . self::SEGMENT_TERMINATOR . "\n";
    }
    
    /**
     * Generate 2010AA loop (Billing Provider Name)
     */
    private function generate2010AA($claim) {
        $edi = '';
        
        // NM1 segment - Billing Provider Name
        $nm1 = [
            'NM1',
            '85',                                          // Entity Identifier Code (Billing Provider)
            '2',                                           // Entity Type Qualifier (Organization)
            $claim['billing_provider']['name'],           // Organization Name
            '',                                            // Name First
            '',                                            // Name Middle
            '',                                            // Name Prefix
            '',                                            // Name Suffix
            'XX',                                          // Identification Code Qualifier (NPI)
            $claim['billing_provider']['npi']              // National Provider Identifier
        ];
        $edi .= implode(self::ELEMENT_SEPARATOR, $nm1) . self::SEGMENT_TERMINATOR . "\n";
        
        // N3 segment - Billing Provider Address
        $n3 = [
            'N3',
            $claim['billing_provider']['address_line1']
        ];
        if (!empty($claim['billing_provider']['address_line2'])) {
            $n3[] = $claim['billing_provider']['address_line2'];
        }
        $edi .= implode(self::ELEMENT_SEPARATOR, $n3) . self::SEGMENT_TERMINATOR . "\n";
        
        // N4 segment - Billing Provider City, State, Zip
        $n4 = [
            'N4',
            $claim['billing_provider']['city'],
            $claim['billing_provider']['state'],
            $claim['billing_provider']['zip']
        ];
        $edi .= implode(self::ELEMENT_SEPARATOR, $n4) . self::SEGMENT_TERMINATOR . "\n";
        
        // REF segment - Billing Provider Tax ID
        if (!empty($claim['billing_provider']['tax_id'])) {
            $ref = [
                'REF',
                'EI',                                      // Reference Identification Qualifier
                $claim['billing_provider']['tax_id']       // Tax ID
            ];
            $edi .= implode(self::ELEMENT_SEPARATOR, $ref) . self::SEGMENT_TERMINATOR . "\n";
        }
        
        return $edi;
    }
    
    /**
     * Generate 2000B loop (Subscriber Hierarchical Level)
     */
    private function generate2000B($claim, $parentId) {
        $hl = [
            'HL',
            $parentId + 1,                                 // Hierarchical ID Number
            $parentId,                                     // Hierarchical Parent ID Number
            '22',                                          // Hierarchical Level Code (Subscriber)
            '0'                                            // Hierarchical Child Code (No subordinate HL)
        ];
        
        return implode(self::ELEMENT_SEPARATOR, $hl) . self::SEGMENT_TERMINATOR . "\n";
    }
    
    /**
     * Generate 2010BA loop (Subscriber Name)
     */
    private function generate2010BA($claim) {
        $edi = '';
        
        // SBR segment - Subscriber Information
        $sbr = [
            'SBR',
            'P',                                           // Payer Responsibility Sequence (Primary)
            '18',                                          // Individual Relationship Code (Self)
            $claim['subscriber']['group_number'] ?? '',    // Reference Identification
            $claim['subscriber']['group_name'] ?? '',      // Name
            'MC',                                          // Insurance Type Code (Medicaid)
            '',                                            // Coordination of Benefits Code
            '',                                            // Yes/No Condition
            '',                                            // Employment Status Code
            $claim['subscriber']['claim_filing_code'] ?? 'MC' // Claim Filing Indicator Code
        ];
        $edi .= implode(self::ELEMENT_SEPARATOR, $sbr) . self::SEGMENT_TERMINATOR . "\n";
        
        // NM1 segment - Subscriber Name
        $nm1 = [
            'NM1',
            'IL',                                          // Entity Identifier Code (Insured)
            '1',                                           // Entity Type Qualifier (Person)
            $claim['subscriber']['last_name'],             // Name Last
            $claim['subscriber']['first_name'],            // Name First
            $claim['subscriber']['middle_name'] ?? '',     // Name Middle
            '',                                            // Name Prefix
            '',                                            // Name Suffix
            'MI',                                          // Identification Code Qualifier (Member ID)
            $claim['subscriber']['member_id']              // Identification Code
        ];
        $edi .= implode(self::ELEMENT_SEPARATOR, $nm1) . self::SEGMENT_TERMINATOR . "\n";
        
        // N3 segment - Subscriber Address
        $n3 = [
            'N3',
            $claim['subscriber']['address_line1']
        ];
        if (!empty($claim['subscriber']['address_line2'])) {
            $n3[] = $claim['subscriber']['address_line2'];
        }
        $edi .= implode(self::ELEMENT_SEPARATOR, $n3) . self::SEGMENT_TERMINATOR . "\n";
        
        // N4 segment - Subscriber City, State, Zip
        $n4 = [
            'N4',
            $claim['subscriber']['city'],
            $claim['subscriber']['state'],
            $claim['subscriber']['zip']
        ];
        $edi .= implode(self::ELEMENT_SEPARATOR, $n4) . self::SEGMENT_TERMINATOR . "\n";
        
        // DMG segment - Subscriber Demographic Information
        $dmg = [
            'DMG',
            'D8',                                          // Date Time Period Format Qualifier
            date('Ymd', strtotime($claim['subscriber']['dob'])), // Date of Birth
            $claim['subscriber']['gender']                 // Gender Code
        ];
        $edi .= implode(self::ELEMENT_SEPARATOR, $dmg) . self::SEGMENT_TERMINATOR . "\n";
        
        return $edi;
    }
    
    /**
     * Generate 2010BB loop (Payer Name)
     */
    private function generate2010BB($claim) {
        $nm1 = [
            'NM1',
            'PR',                                          // Entity Identifier Code (Payer)
            '2',                                           // Entity Type Qualifier (Non-Person)
            'MARYLAND MEDICAID',                           // Payer Name
            '',                                            // Name First
            '',                                            // Name Middle
            '',                                            // Name Prefix
            '',                                            // Name Suffix
            'PI',                                          // Identification Code Qualifier (Payer ID)
            $claim['payer']['payer_id'] ?? 'MDMEDICAID'   // Payer Identification
        ];
        
        return implode(self::ELEMENT_SEPARATOR, $nm1) . self::SEGMENT_TERMINATOR . "\n";
    }
    
    /**
     * Generate 2300 loop (Claim Information)
     */
    private function generate2300($claim) {
        $edi = '';
        
        // CLM segment - Claim Information
        $clm = [
            'CLM',
            $claim['claim_number'],                        // Claim Submitter's Identifier
            $claim['total_amount'],                        // Total Claim Charge Amount
            '',                                            // Claim Filing Indicator Code
            '',                                            // Non-Institutional Claim Type Code
            $claim['facility_code'] . self::SUBELEMENT_SEPARATOR . 
                'B' . self::SUBELEMENT_SEPARATOR . '1',   // Health Care Service Location
            'Y',                                           // Provider Accept Assignment Code
            'A',                                           // Assignment of Benefits Code
            'Y',                                           // Release of Information Code
            'P'                                            // Patient Signature Source Code
        ];
        $edi .= implode(self::ELEMENT_SEPARATOR, $clm) . self::SEGMENT_TERMINATOR . "\n";
        
        // DTP segments - Date/Time References
        // Statement Date
        $dtp = [
            'DTP',
            '434',                                         // Date/Time Qualifier (Statement)
            'RD8',                                         // Date Time Period Format Qualifier
            date('Ymd', strtotime($claim['statement_from_date'])) . '-' . 
            date('Ymd', strtotime($claim['statement_to_date']))
        ];
        $edi .= implode(self::ELEMENT_SEPARATOR, $dtp) . self::SEGMENT_TERMINATOR . "\n";
        
        // HI segment - Health Care Diagnosis Code
        $hi = ['HI'];
        foreach ($claim['diagnoses'] as $index => $diagnosis) {
            $prefix = ($index == 0) ? 'ABK' : 'ABF';     // Primary vs Secondary
            $hi[] = $prefix . self::SUBELEMENT_SEPARATOR . $diagnosis['code'];
        }
        $edi .= implode(self::ELEMENT_SEPARATOR, $hi) . self::SEGMENT_TERMINATOR . "\n";
        
        return $edi;
    }
    
    /**
     * Generate 2310A loop (Referring Provider Name)
     */
    private function generate2310A($claim) {
        $nm1 = [
            'NM1',
            'DN',                                          // Entity Identifier Code (Referring Provider)
            '1',                                           // Entity Type Qualifier (Person)
            $claim['referring_provider']['last_name'],
            $claim['referring_provider']['first_name'],
            $claim['referring_provider']['middle_name'] ?? '',
            '',                                            // Name Prefix
            '',                                            // Name Suffix
            'XX',                                          // Identification Code Qualifier (NPI)
            $claim['referring_provider']['npi']
        ];
        
        return implode(self::ELEMENT_SEPARATOR, $nm1) . self::SEGMENT_TERMINATOR . "\n";
    }
    
    /**
     * Generate 2310B loop (Rendering Provider Name)
     */
    private function generate2310B($claim) {
        $edi = '';
        
        // Only include if different from billing provider
        if ($claim['rendering_provider']['npi'] != $claim['billing_provider']['npi']) {
            $nm1 = [
                'NM1',
                '82',                                      // Entity Identifier Code (Rendering Provider)
                '1',                                       // Entity Type Qualifier (Person)
                $claim['rendering_provider']['last_name'],
                $claim['rendering_provider']['first_name'],
                $claim['rendering_provider']['middle_name'] ?? '',
                '',                                        // Name Prefix
                '',                                        // Name Suffix
                'XX',                                      // Identification Code Qualifier (NPI)
                $claim['rendering_provider']['npi']
            ];
            $edi .= implode(self::ELEMENT_SEPARATOR, $nm1) . self::SEGMENT_TERMINATOR . "\n";
            
            // PRV segment - Rendering Provider Specialty
            if (!empty($claim['rendering_provider']['taxonomy_code'])) {
                $prv = [
                    'PRV',
                    'PE',                                  // Provider Code
                    'PXC',                                 // Reference Identification Qualifier
                    $claim['rendering_provider']['taxonomy_code']
                ];
                $edi .= implode(self::ELEMENT_SEPARATOR, $prv) . self::SEGMENT_TERMINATOR . "\n";
            }
        }
        
        return $edi;
    }
    
    /**
     * Generate 2400 loops (Service Lines)
     */
    private function generate2400($claim) {
        $edi = '';
        $lineNumber = 0;
        
        foreach ($claim['service_lines'] as $service) {
            $lineNumber++;
            
            // LX segment - Service Line Number
            $lx = [
                'LX',
                $lineNumber
            ];
            $edi .= implode(self::ELEMENT_SEPARATOR, $lx) . self::SEGMENT_TERMINATOR . "\n";
            
            // SV1 segment - Professional Service
            $sv1 = [
                'SV1',
                'HC' . self::SUBELEMENT_SEPARATOR . 
                    $service['procedure_code'] . 
                    ($service['modifier1'] ? self::SUBELEMENT_SEPARATOR . $service['modifier1'] : '') .
                    ($service['modifier2'] ? self::SUBELEMENT_SEPARATOR . $service['modifier2'] : '') .
                    ($service['modifier3'] ? self::SUBELEMENT_SEPARATOR . $service['modifier3'] : '') .
                    ($service['modifier4'] ? self::SUBELEMENT_SEPARATOR . $service['modifier4'] : ''),
                $service['charge_amount'],                // Line Item Charge Amount
                'UN',                                      // Unit or Basis for Measurement Code
                $service['units'],                         // Service Unit Count
                $service['place_of_service'],              // Place of Service Code
                '',                                        // Service Type Code
                $service['diagnosis_pointer'] ?? '1'       // Diagnosis Code Pointer
            ];
            $edi .= implode(self::ELEMENT_SEPARATOR, $sv1) . self::SEGMENT_TERMINATOR . "\n";
            
            // DTP segment - Service Date
            $dtp = [
                'DTP',
                '472',                                     // Date/Time Qualifier (Service)
                'D8',                                      // Date Time Period Format Qualifier
                date('Ymd', strtotime($service['service_date']))
            ];
            $edi .= implode(self::ELEMENT_SEPARATOR, $dtp) . self::SEGMENT_TERMINATOR . "\n";
            
            // REF segment - Service Line Reference (if applicable)
            if (!empty($service['prior_auth_number'])) {
                $ref = [
                    'REF',
                    'G1',                                  // Reference Identification Qualifier
                    $service['prior_auth_number']          // Prior Authorization Number
                ];
                $edi .= implode(self::ELEMENT_SEPARATOR, $ref) . self::SEGMENT_TERMINATOR . "\n";
            }
        }
        
        return $edi;
    }
    
    /**
     * Generate SE segment (Transaction Set Trailer)
     */
    private function generateSE($segmentCount) {
        $se = [
            'SE',
            $segmentCount,                                 // Number of Included Segments
            str_pad($this->controlNumber, 4, '0', STR_PAD_LEFT) // Transaction Set Control Number
        ];
        
        return implode(self::ELEMENT_SEPARATOR, $se) . self::SEGMENT_TERMINATOR . "\n";
    }
    
    /**
     * Generate GE segment (Functional Group Trailer)
     */
    private function generateGE() {
        $ge = [
            'GE',
            '1',                                           // Number of Transaction Sets Included
            $this->groupControlNumber                      // Group Control Number
        ];
        
        return implode(self::ELEMENT_SEPARATOR, $ge) . self::SEGMENT_TERMINATOR . "\n";
    }
    
    /**
     * Generate IEA segment (Interchange Control Trailer)
     */
    private function generateIEA() {
        $iea = [
            'IEA',
            '1',                                           // Number of Included Functional Groups
            str_pad($this->interchangeControlNumber, 9, '0', STR_PAD_LEFT) // Interchange Control Number
        ];
        
        return implode(self::ELEMENT_SEPARATOR, $iea) . self::SEGMENT_TERMINATOR . "\n";
    }
    
    /**
     * Validate claim data
     * 
     * @param array $claimData
     * @return array Validation result
     */
    private function validateClaimData($claimData) {
        $errors = [];
        $required = [
            'claim_number' => 'Claim number',
            'total_amount' => 'Total amount',
            'statement_from_date' => 'Statement from date',
            'statement_to_date' => 'Statement to date',
            'facility_code' => 'Facility code',
            'billing_provider' => 'Billing provider information',
            'subscriber' => 'Subscriber information',
            'diagnoses' => 'Diagnosis codes',
            'service_lines' => 'Service lines'
        ];
        
        // Check required fields
        foreach ($required as $field => $label) {
            if (empty($claimData[$field])) {
                $errors[] = "$label is required";
            }
        }
        
        // Validate billing provider
        if (!empty($claimData['billing_provider'])) {
            if (empty($claimData['billing_provider']['npi'])) {
                $errors[] = "Billing provider NPI is required";
            }
            if (empty($claimData['billing_provider']['name'])) {
                $errors[] = "Billing provider name is required";
            }
        }
        
        // Validate subscriber
        if (!empty($claimData['subscriber'])) {
            if (empty($claimData['subscriber']['member_id'])) {
                $errors[] = "Subscriber member ID is required";
            }
            if (empty($claimData['subscriber']['last_name'])) {
                $errors[] = "Subscriber last name is required";
            }
            if (empty($claimData['subscriber']['first_name'])) {
                $errors[] = "Subscriber first name is required";
            }
        }
        
        // Validate service lines
        if (!empty($claimData['service_lines'])) {
            foreach ($claimData['service_lines'] as $index => $service) {
                if (empty($service['procedure_code'])) {
                    $errors[] = "Procedure code is required for service line " . ($index + 1);
                }
                if (empty($service['charge_amount'])) {
                    $errors[] = "Charge amount is required for service line " . ($index + 1);
                }
                if (empty($service['units'])) {
                    $errors[] = "Units are required for service line " . ($index + 1);
                }
                if (empty($service['service_date'])) {
                    $errors[] = "Service date is required for service line " . ($index + 1);
                }
                
                // Validate autism waiver service codes
                if (!empty($service['procedure_code']) && 
                    !array_key_exists($service['procedure_code'], self::AUTISM_SERVICE_CODES)) {
                    $errors[] = "Invalid autism waiver service code: " . $service['procedure_code'];
                }
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Generate unique control number
     * 
     * @return string Control number
     */
    private function generateControlNumber() {
        return str_pad(mt_rand(1, 999999999), 9, '0', STR_PAD_LEFT);
    }
    
    /**
     * Update claims with EDI file ID
     * 
     * @param int $fileId
     */
    private function updateClaimsWithFileId($fileId) {
        try {
            $claimIds = array_column($this->claims, 'claim_id');
            if (!empty($claimIds)) {
                $placeholders = implode(',', array_fill(0, count($claimIds), '?'));
                $sql = "UPDATE billing_claims 
                        SET edi_file_id = ?, 
                            edi_status = 'submitted',
                            edi_submitted_at = NOW() 
                        WHERE id IN ($placeholders)";
                
                $params = array_merge([$fileId], $claimIds);
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
            }
        } catch (\Exception $e) {
            $this->log("Error updating claims with file ID: " . $e->getMessage(), 'error');
        }
    }
    
    /**
     * Get errors
     * 
     * @return array
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Log message
     * 
     * @param string $message
     * @param string $level
     */
    private function log($message, $level = 'info') {
        if ($this->logger) {
            $this->logger->log($level, $message);
        }
        
        // Also log to database
        try {
            $stmt = $this->db->prepare("
                INSERT INTO edi_logs (
                    log_level,
                    message,
                    context,
                    created_at
                ) VALUES (?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $level,
                $message,
                json_encode([
                    'interchange_control_number' => $this->interchangeControlNumber,
                    'group_control_number' => $this->groupControlNumber,
                    'claim_count' => count($this->claims)
                ])
            ]);
        } catch (\Exception $e) {
            // Silently fail logging
        }
    }
}