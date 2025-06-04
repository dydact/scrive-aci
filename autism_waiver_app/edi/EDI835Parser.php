<?php
/**
 * EDI 835 Remittance Advice Parser
 * 
 * Parses HIPAA 5010 compliant EDI 835 remittance advice files from payers
 * Specifically designed for Maryland Medicaid autism waiver services
 * Handles payment posting, adjustments, denials, and reconciliation
 * 
 * @package     ScriverACI
 * @subpackage  EDI
 * @author      Scriver ACI Development Team
 * @version     1.0.0
 */

namespace ScriverACI\EDI;

class EDI835Parser {
    
    // EDI Delimiters
    const SEGMENT_TERMINATOR = '~';
    const ELEMENT_SEPARATOR = '*';
    const SUBELEMENT_SEPARATOR = ':';
    const REPETITION_SEPARATOR = '^';
    
    // Adjustment reason codes commonly used by Maryland Medicaid
    const ADJUSTMENT_REASON_CODES = [
        '1' => 'Deductible Amount',
        '2' => 'Coinsurance Amount',
        '3' => 'Co-payment Amount',
        '4' => 'The procedure code is inconsistent with the modifier used',
        '5' => 'The procedure code/bill type is inconsistent with the place of service',
        '6' => 'The procedure/revenue code is inconsistent with the patient\'s age',
        '7' => 'The procedure/revenue code is inconsistent with the patient\'s gender',
        '8' => 'The procedure code is inconsistent with the provider type/specialty',
        '9' => 'The diagnosis is inconsistent with the patient\'s age',
        '10' => 'The diagnosis is inconsistent with the patient\'s gender',
        '11' => 'The diagnosis is inconsistent with the procedure',
        '12' => 'The diagnosis is inconsistent with the provider type',
        '15' => 'Payment adjusted because this service is not covered',
        '16' => 'Claim/service lacks information needed for adjudication',
        '18' => 'Duplicate claim/service',
        '19' => 'This is a work-related injury/illness',
        '20' => 'This injury/illness is covered by the liability carrier',
        '21' => 'This injury/illness is the liability of the no-fault carrier',
        '22' => 'This care may be covered by another payer per coordination of benefits',
        '23' => 'The impact of prior payer(s) adjudication',
        '24' => 'Charges are covered under a capitation agreement/managed care plan',
        '26' => 'Expenses incurred prior to coverage',
        '27' => 'Expenses incurred after coverage terminated',
        '29' => 'The time limit for filing has expired',
        '31' => 'Patient cannot be identified as our insured',
        '32' => 'Our records indicate that this dependent is not an eligible dependent',
        '33' => 'Insured has no dependent coverage',
        '34' => 'Insured has no coverage for newborns',
        '35' => 'Lifetime benefit maximum has been reached',
        '45' => 'Charges exceed your contracted/legislated fee arrangement',
        '49' => 'This is a non-covered service',
        '50' => 'These are non-covered services because this is not deemed a medical necessity',
        '51' => 'These are non-covered services because this is a pre-existing condition',
        '54' => 'Multiple physicians/assistants are not covered',
        '55' => 'Procedure/treatment is deemed experimental/investigational',
        '56' => 'Procedure/treatment has not been deemed proven effective',
        '58' => 'Treatment was deemed by the payer to have been rendered in an inappropriate setting',
        '59' => 'Charges are adjusted based on multiple surgery rules',
        '60' => 'Charges for outpatient services are not covered when performed within a period of time',
        '96' => 'Non-covered charges',
        '97' => 'Payment adjusted because this procedure/service is not paid separately',
        '109' => 'Claim not covered by this payer/contractor',
        '110' => 'Billing date predates service date',
        '111' => 'Not covered unless the provider accepts assignment',
        '112' => 'Service not furnished directly to the patient',
        '114' => 'Procedure/product not approved by the FDA',
        '115' => 'Procedure postponed or canceled',
        '116' => 'The advance indemnification notice signed by the patient',
        '117' => 'Transportation is only covered to the closest appropriate facility',
        '118' => 'ESRD network support adjustment',
        '119' => 'Benefit maximum for this time period has been reached',
        '121' => 'Indemnification adjustment',
        '122' => 'Psychiatric reduction',
        '125' => 'Submission/billing error(s)',
        '128' => 'Newborn\'s services are covered in the mother\'s allowance',
        '129' => 'Prior processing information appears incorrect',
        '130' => 'Claim submission fee',
        '131' => 'Claim specific negotiated discount',
        '132' => 'Prearranged demonstration project adjustment',
        '133' => 'The disposition of this claim/service is pending further review',
        '134' => 'Technical fees removed from charges',
        '135' => 'Interim bills cannot be processed',
        '136' => 'Failure to follow prior authorization guidelines',
        '137' => 'Regulatory surcharges, assessments or health related taxes',
        '138' => 'Appeal procedures not followed',
        '139' => 'Contracted funding agreement',
        '140' => 'Patient/Insured health identification number and name do not match',
        '141' => 'Claim spans eligible and ineligible periods of coverage',
        '142' => 'Monthly benefit has been paid',
        '143' => 'Portion of payment deferred',
        '144' => 'Incentive adjustment',
        '146' => 'Diagnosis was invalid for the date of service',
        '147' => 'Provider contracted/negotiated rate expired',
        '148' => 'Information from another provider was not provided',
        '149' => 'Lifetime benefit maximum has been reached for this service',
        '150' => 'Payer deems the information submitted does not support this level of service',
        '151' => 'Payment adjusted because the payer deems the information submitted does not support this many services',
        '152' => 'Payer deems the information submitted does not support this length of service',
        '153' => 'Payer deems the information submitted does not support this dosage',
        '154' => 'Payer deems the information submitted does not support this day\'s supply',
        '155' => 'Patient refused the service/procedure',
        '157' => 'Service/procedure was provided as a result of an act of war',
        '158' => 'Service/procedure was provided outside of the United States',
        '159' => 'Service/procedure was provided as a result of terrorism',
        '160' => 'Injury/illness was the result of an activity that is a benefit exclusion',
        '161' => 'Provider performance bonus',
        '162' => 'State-mandated Requirement for Property and Casualty',
        '163' => 'Attachment referenced on the claim was not received',
        '164' => 'Attachment referenced on the claim was not received in a timely fashion',
        '165' => 'Referral absent or exceeded',
        '166' => 'These services were submitted after this payers responsibility',
        '167' => 'This (these) diagnosis(es) is (are) not covered',
        '168' => 'Service(s) have been considered under the patient\'s medical plan',
        '169' => 'Alternate benefit has been provided',
        '170' => 'Payment is denied when performed by this type of provider',
        '171' => 'Payment is denied when performed by this type of provider in this type of facility',
        '172' => 'Payment is adjusted when performed in this type of facility',
        '173' => 'Service was not prescribed by a physician',
        '174' => 'Service was not prescribed prior to delivery',
        '175' => 'Prescription is incomplete',
        '176' => 'Prescription is not current',
        '177' => 'Patient has not met the required eligibility requirements',
        '178' => 'Patient has not met the required spend down requirements',
        '179' => 'Patient has not met the required waiting requirements',
        '180' => 'Patient has not met the required residency requirements',
        '181' => 'Procedure code was invalid on the date of service',
        '182' => 'Procedure modifier was invalid on the date of service',
        '183' => 'The referring provider is not eligible to refer',
        '184' => 'The prescribing/ordering provider is not eligible to prescribe/order',
        '185' => 'The rendering provider is not eligible to perform the service billed',
        '186' => 'Level of care change adjustment',
        '187' => 'Consumer Spending Account payments',
        '188' => 'This product/procedure is only covered when used according to FDA recommendations',
        '189' => 'Not otherwise classified or unlisted procedure code was billed',
        '190' => 'Payment is included in the allowance for a Skilled Nursing Facility',
        '191' => 'Not a work related injury/illness',
        '192' => 'Non standard adjustment code from paper remittance advice',
        '193' => 'Original payment decision is being maintained',
        '194' => 'Anesthesia performed by the operating physician',
        '195' => 'Refund issued to an erroneous priority payer',
        '197' => 'Precertification/authorization absent',
        '198' => 'Precertification/authorization exceeded',
        '199' => 'Revenue code and Procedure code do not match',
        '200' => 'Expenses incurred during lapse in coverage'
    ];
    
    // Adjustment group codes
    const ADJUSTMENT_GROUP_CODES = [
        'CO' => 'Contractual Obligations',
        'CR' => 'Corrections and Reversals',
        'OA' => 'Other Adjustments',
        'PI' => 'Payer Initiated Reductions',
        'PR' => 'Patient Responsibility'
    ];
    
    // Claim status codes
    const CLAIM_STATUS_CODES = [
        '1' => 'Processed as Primary',
        '2' => 'Processed as Secondary',
        '3' => 'Processed as Tertiary',
        '4' => 'Denied',
        '5' => 'Pended',
        '19' => 'Processed as Primary, Forwarded to Additional Payer(s)',
        '20' => 'Processed as Secondary, Forwarded to Additional Payer(s)',
        '21' => 'Processed as Tertiary, Forwarded to Additional Payer(s)',
        '22' => 'Reversal of Previous Payment',
        '23' => 'Not Our Claim, Forwarded to Additional Payer(s)'
    ];
    
    private $db;
    private $segments = [];
    private $currentIndex = 0;
    private $errors = [];
    private $warnings = [];
    private $logger;
    
    // Parsed data containers
    private $header = [];
    private $payer = [];
    private $payee = [];
    private $claims = [];
    private $providerAdjustments = [];
    private $summary = [];
    
    /**
     * Constructor
     * 
     * @param PDO $database Database connection
     * @param object $logger Logger instance (optional)
     */
    public function __construct($database, $logger = null) {
        $this->db = $database;
        $this->logger = $logger;
    }
    
    /**
     * Parse EDI 835 file content
     * 
     * @param string $ediContent EDI file content
     * @return bool Success status
     */
    public function parse($ediContent) {
        try {
            $this->reset();
            
            // Split content into segments
            $this->segments = explode(self::SEGMENT_TERMINATOR, trim($ediContent));
            
            // Remove empty segments
            $this->segments = array_filter($this->segments, function($segment) {
                return !empty(trim($segment));
            });
            
            // Reset array keys
            $this->segments = array_values($this->segments);
            
            if (empty($this->segments)) {
                $this->errors[] = "No valid segments found in EDI file";
                return false;
            }
            
            // Parse the EDI structure
            while ($this->currentIndex < count($this->segments)) {
                $segment = $this->getCurrentSegment();
                
                if (!$segment) {
                    $this->currentIndex++;
                    continue;
                }
                
                $segmentType = $this->getSegmentType($segment);
                
                switch ($segmentType) {
                    case 'ISA':
                        $this->parseISA($segment);
                        break;
                    case 'GS':
                        $this->parseGS($segment);
                        break;
                    case 'ST':
                        $this->parseST($segment);
                        break;
                    case 'BPR':
                        $this->parseBPR($segment);
                        break;
                    case 'TRN':
                        $this->parseTRN($segment);
                        break;
                    case 'N1':
                        $this->parseN1($segment);
                        break;
                    case 'CLP':
                        $this->parseCLP($segment);
                        break;
                    case 'CAS':
                        $this->parseCAS($segment);
                        break;
                    case 'SVC':
                        $this->parseSVC($segment);
                        break;
                    case 'PLB':
                        $this->parsePLB($segment);
                        break;
                    case 'SE':
                        $this->parseSE($segment);
                        break;
                    case 'GE':
                        $this->parseGE($segment);
                        break;
                    case 'IEA':
                        $this->parseIEA($segment);
                        break;
                }
                
                $this->currentIndex++;
            }
            
            $this->log("Successfully parsed EDI 835 with " . count($this->claims) . " claims");
            return true;
            
        } catch (\Exception $e) {
            $this->errors[] = "Error parsing EDI 835: " . $e->getMessage();
            $this->log("Error parsing EDI 835: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Process parsed remittance data
     * Creates payment batch and posts payments to claims
     * 
     * @return array Processing result
     */
    public function processRemittance() {
        try {
            if (empty($this->claims)) {
                throw new \Exception("No claims found to process");
            }
            
            $this->db->beginTransaction();
            
            // Create payment batch
            $batchId = $this->createPaymentBatch();
            
            // Process each claim
            $processedCount = 0;
            $totalPosted = 0;
            $totalAdjustments = 0;
            
            foreach ($this->claims as $claim) {
                $result = $this->processClaimPayment($claim, $batchId);
                
                if ($result['success']) {
                    $processedCount++;
                    $totalPosted += $result['payment_amount'];
                    $totalAdjustments += $result['adjustment_amount'];
                }
            }
            
            // Process provider level adjustments if any
            if (!empty($this->providerAdjustments)) {
                $this->processProviderAdjustments($batchId);
            }
            
            // Update batch totals
            $this->updateBatchTotals($batchId, $processedCount, $totalPosted, $totalAdjustments);
            
            $this->db->commit();
            
            $this->log("Remittance processed successfully: Batch ID $batchId, Claims: $processedCount");
            
            return [
                'success' => true,
                'batch_id' => $batchId,
                'claims_processed' => $processedCount,
                'total_posted' => $totalPosted,
                'total_adjustments' => $totalAdjustments
            ];
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->errors[] = "Error processing remittance: " . $e->getMessage();
            $this->log("Error processing remittance: " . $e->getMessage(), 'error');
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate reconciliation report
     * 
     * @param int $batchId Payment batch ID
     * @return array Report data
     */
    public function generateReconciliationReport($batchId = null) {
        try {
            $report = [
                'header' => $this->header,
                'payer' => $this->payer,
                'payee' => $this->payee,
                'summary' => $this->summary,
                'claims' => [],
                'provider_adjustments' => $this->providerAdjustments,
                'totals' => [
                    'total_payment' => 0,
                    'total_adjustments' => 0,
                    'total_patient_responsibility' => 0,
                    'claim_count' => count($this->claims),
                    'paid_claims' => 0,
                    'denied_claims' => 0,
                    'partial_payment_claims' => 0
                ]
            ];
            
            foreach ($this->claims as $claim) {
                $claimReport = [
                    'claim_number' => $claim['claim_number'],
                    'patient_name' => $claim['patient_name'],
                    'status' => $claim['claim_status_code'],
                    'status_description' => self::CLAIM_STATUS_CODES[$claim['claim_status_code']] ?? 'Unknown',
                    'total_charge' => $claim['total_charge'],
                    'payment_amount' => $claim['payment_amount'],
                    'patient_responsibility' => $claim['patient_responsibility'],
                    'adjustments' => [],
                    'service_lines' => []
                ];
                
                // Add claim level adjustments
                if (!empty($claim['adjustments'])) {
                    foreach ($claim['adjustments'] as $adj) {
                        $claimReport['adjustments'][] = [
                            'group' => $adj['group_code'],
                            'group_description' => self::ADJUSTMENT_GROUP_CODES[$adj['group_code']] ?? 'Unknown',
                            'reason' => $adj['reason_code'],
                            'reason_description' => self::ADJUSTMENT_REASON_CODES[$adj['reason_code']] ?? 'Unknown',
                            'amount' => $adj['amount']
                        ];
                    }
                }
                
                // Add service line details
                if (!empty($claim['service_lines'])) {
                    foreach ($claim['service_lines'] as $service) {
                        $serviceLine = [
                            'procedure_code' => $service['procedure_code'],
                            'modifiers' => $service['modifiers'],
                            'charge_amount' => $service['charge_amount'],
                            'payment_amount' => $service['payment_amount'],
                            'units_paid' => $service['units_paid'],
                            'adjustments' => []
                        ];
                        
                        if (!empty($service['adjustments'])) {
                            foreach ($service['adjustments'] as $adj) {
                                $serviceLine['adjustments'][] = [
                                    'group' => $adj['group_code'],
                                    'group_description' => self::ADJUSTMENT_GROUP_CODES[$adj['group_code']] ?? 'Unknown',
                                    'reason' => $adj['reason_code'],
                                    'reason_description' => self::ADJUSTMENT_REASON_CODES[$adj['reason_code']] ?? 'Unknown',
                                    'amount' => $adj['amount']
                                ];
                            }
                        }
                        
                        $claimReport['service_lines'][] = $serviceLine;
                    }
                }
                
                $report['claims'][] = $claimReport;
                
                // Update totals
                $report['totals']['total_payment'] += $claim['payment_amount'];
                $report['totals']['total_patient_responsibility'] += $claim['patient_responsibility'];
                
                if ($claim['claim_status_code'] == '4') {
                    $report['totals']['denied_claims']++;
                } elseif ($claim['payment_amount'] > 0 && $claim['payment_amount'] < $claim['total_charge']) {
                    $report['totals']['partial_payment_claims']++;
                } elseif ($claim['payment_amount'] > 0) {
                    $report['totals']['paid_claims']++;
                }
            }
            
            // Calculate total adjustments
            $report['totals']['total_adjustments'] = array_sum(array_map(function($claim) {
                $claimAdj = array_sum(array_column($claim['adjustments'] ?? [], 'amount'));
                $serviceAdj = 0;
                foreach ($claim['service_lines'] ?? [] as $service) {
                    $serviceAdj += array_sum(array_column($service['adjustments'] ?? [], 'amount'));
                }
                return $claimAdj + $serviceAdj;
            }, $this->claims));
            
            return $report;
            
        } catch (\Exception $e) {
            $this->errors[] = "Error generating reconciliation report: " . $e->getMessage();
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Parse ISA segment (Interchange Control Header)
     */
    private function parseISA($segment) {
        $elements = $this->getSegmentElements($segment);
        
        if (count($elements) < 17) {
            $this->warnings[] = "ISA segment has insufficient elements";
            return;
        }
        
        $this->header['interchange_control_number'] = $elements[13];
        $this->header['interchange_date'] = $elements[9];
        $this->header['interchange_time'] = $elements[10];
        $this->header['test_indicator'] = $elements[15];
    }
    
    /**
     * Parse GS segment (Functional Group Header)
     */
    private function parseGS($segment) {
        $elements = $this->getSegmentElements($segment);
        
        if (count($elements) < 9) {
            $this->warnings[] = "GS segment has insufficient elements";
            return;
        }
        
        $this->header['group_control_number'] = $elements[6];
        $this->header['functional_id'] = $elements[1];
        $this->header['application_sender'] = $elements[2];
        $this->header['application_receiver'] = $elements[3];
    }
    
    /**
     * Parse ST segment (Transaction Set Header)
     */
    private function parseST($segment) {
        $elements = $this->getSegmentElements($segment);
        
        if (count($elements) < 3) {
            $this->warnings[] = "ST segment has insufficient elements";
            return;
        }
        
        $this->header['transaction_set_control_number'] = $elements[2];
        $this->header['implementation_convention'] = $elements[3] ?? '';
    }
    
    /**
     * Parse BPR segment (Financial Information)
     */
    private function parseBPR($segment) {
        $elements = $this->getSegmentElements($segment);
        
        if (count($elements) < 14) {
            $this->warnings[] = "BPR segment has insufficient elements";
            return;
        }
        
        $this->summary['transaction_handling_code'] = $elements[1];
        $this->summary['total_payment_amount'] = floatval($elements[2]);
        $this->summary['credit_debit_flag'] = $elements[3];
        $this->summary['payment_method'] = $elements[4];
        $this->summary['payment_format'] = $elements[5] ?? '';
        $this->summary['sender_bank_id'] = $elements[12] ?? '';
        $this->summary['sender_account_number'] = $elements[13] ?? '';
        $this->summary['check_issue_date'] = $elements[16] ?? '';
    }
    
    /**
     * Parse TRN segment (Trace)
     */
    private function parseTRN($segment) {
        $elements = $this->getSegmentElements($segment);
        
        if (count($elements) < 3) {
            $this->warnings[] = "TRN segment has insufficient elements";
            return;
        }
        
        $this->summary['trace_type'] = $elements[1];
        $this->summary['check_or_eft_number'] = $elements[2];
        $this->summary['payer_id'] = $elements[3] ?? '';
    }
    
    /**
     * Parse N1 segment (Name)
     */
    private function parseN1($segment) {
        $elements = $this->getSegmentElements($segment);
        
        if (count($elements) < 3) {
            $this->warnings[] = "N1 segment has insufficient elements";
            return;
        }
        
        $entityCode = $elements[1];
        $name = $elements[2];
        $idQualifier = $elements[3] ?? '';
        $id = $elements[4] ?? '';
        
        switch ($entityCode) {
            case 'PR': // Payer
                $this->payer = [
                    'name' => $name,
                    'id_qualifier' => $idQualifier,
                    'id' => $id
                ];
                break;
            case 'PE': // Payee
                $this->payee = [
                    'name' => $name,
                    'id_qualifier' => $idQualifier,
                    'id' => $id
                ];
                break;
        }
    }
    
    /**
     * Parse CLP segment (Claim Payment Information)
     */
    private function parseCLP($segment) {
        $elements = $this->getSegmentElements($segment);
        
        if (count($elements) < 8) {
            $this->warnings[] = "CLP segment has insufficient elements";
            return;
        }
        
        $claim = [
            'claim_number' => $elements[1],
            'claim_status_code' => $elements[2],
            'total_charge' => floatval($elements[3]),
            'payment_amount' => floatval($elements[4]),
            'patient_responsibility' => floatval($elements[5] ?? 0),
            'claim_filing_indicator' => $elements[6] ?? '',
            'payer_claim_control_number' => $elements[7] ?? '',
            'facility_code' => $elements[8] ?? '',
            'frequency_code' => $elements[9] ?? '',
            'patient_name' => '',
            'patient_id' => '',
            'service_dates' => [],
            'adjustments' => [],
            'service_lines' => []
        ];
        
        // Parse subsequent segments related to this claim
        $this->currentIndex++;
        while ($this->currentIndex < count($this->segments)) {
            $nextSegment = $this->getCurrentSegment();
            $nextType = $this->getSegmentType($nextSegment);
            
            if ($nextType === 'CLP' || $nextType === 'PLB' || $nextType === 'SE') {
                $this->currentIndex--;
                break;
            }
            
            switch ($nextType) {
                case 'NM1':
                    $this->parseClaimNM1($nextSegment, $claim);
                    break;
                case 'DTM':
                    $this->parseClaimDTM($nextSegment, $claim);
                    break;
                case 'CAS':
                    $this->parseClaimCAS($nextSegment, $claim);
                    break;
                case 'SVC':
                    $this->parseSVC($nextSegment, $claim);
                    break;
            }
            
            $this->currentIndex++;
        }
        
        $this->claims[] = $claim;
    }
    
    /**
     * Parse NM1 segment within claim context
     */
    private function parseClaimNM1($segment, &$claim) {
        $elements = $this->getSegmentElements($segment);
        
        if (count($elements) < 3) {
            return;
        }
        
        $entityCode = $elements[1];
        
        switch ($entityCode) {
            case 'QC': // Patient
                $claim['patient_name'] = trim(
                    ($elements[3] ?? '') . ', ' . 
                    ($elements[4] ?? '') . ' ' . 
                    ($elements[5] ?? '')
                );
                $claim['patient_id'] = $elements[9] ?? '';
                break;
            case '82': // Rendering Provider
                $claim['rendering_provider'] = [
                    'name' => trim(
                        ($elements[3] ?? '') . ', ' . 
                        ($elements[4] ?? '') . ' ' . 
                        ($elements[5] ?? '')
                    ),
                    'npi' => $elements[9] ?? ''
                ];
                break;
        }
    }
    
    /**
     * Parse DTM segment within claim context
     */
    private function parseClaimDTM($segment, &$claim) {
        $elements = $this->getSegmentElements($segment);
        
        if (count($elements) < 3) {
            return;
        }
        
        $dateQualifier = $elements[1];
        $date = $elements[2];
        
        switch ($dateQualifier) {
            case '232': // Claim statement period start
                $claim['service_dates']['start'] = $date;
                break;
            case '233': // Claim statement period end
                $claim['service_dates']['end'] = $date;
                break;
        }
    }
    
    /**
     * Parse CAS segment (Claim Adjustment)
     */
    private function parseCAS($segment) {
        $elements = $this->getSegmentElements($segment);
        
        if (count($elements) < 4) {
            $this->warnings[] = "CAS segment has insufficient elements";
            return [];
        }
        
        $adjustments = [];
        $groupCode = $elements[1];
        
        // CAS can have up to 6 adjustment reason sets
        for ($i = 2; $i < count($elements); $i += 3) {
            if (isset($elements[$i]) && isset($elements[$i + 1])) {
                $adjustments[] = [
                    'group_code' => $groupCode,
                    'reason_code' => $elements[$i],
                    'amount' => floatval($elements[$i + 1]),
                    'quantity' => $elements[$i + 2] ?? null
                ];
            }
        }
        
        return $adjustments;
    }
    
    /**
     * Parse claim level CAS segment
     */
    private function parseClaimCAS($segment, &$claim) {
        $adjustments = $this->parseCAS($segment);
        $claim['adjustments'] = array_merge($claim['adjustments'], $adjustments);
    }
    
    /**
     * Parse SVC segment (Service Payment Information)
     */
    private function parseSVC($segment, &$claim = null) {
        $elements = $this->getSegmentElements($segment);
        
        if (count($elements) < 7) {
            $this->warnings[] = "SVC segment has insufficient elements";
            return;
        }
        
        // Parse composite procedure code
        $procedureComposite = explode(self::SUBELEMENT_SEPARATOR, $elements[1]);
        $procedureCode = $procedureComposite[1] ?? '';
        $modifiers = array_filter([
            $procedureComposite[2] ?? '',
            $procedureComposite[3] ?? '',
            $procedureComposite[4] ?? '',
            $procedureComposite[5] ?? ''
        ]);
        
        $service = [
            'procedure_code' => $procedureCode,
            'modifiers' => $modifiers,
            'charge_amount' => floatval($elements[2]),
            'payment_amount' => floatval($elements[3]),
            'revenue_code' => $elements[4] ?? '',
            'units_paid' => floatval($elements[5] ?? 0),
            'original_units' => floatval($elements[6] ?? 0),
            'adjustments' => [],
            'dates' => []
        ];
        
        // Parse subsequent segments related to this service
        $tempIndex = $this->currentIndex + 1;
        while ($tempIndex < count($this->segments)) {
            $nextSegment = $this->segments[$tempIndex];
            $nextType = $this->getSegmentType($nextSegment);
            
            if ($nextType === 'SVC' || $nextType === 'CLP' || $nextType === 'PLB' || $nextType === 'SE') {
                break;
            }
            
            switch ($nextType) {
                case 'DTM':
                    $this->parseServiceDTM($nextSegment, $service);
                    break;
                case 'CAS':
                    $serviceAdjustments = $this->parseCAS($nextSegment);
                    $service['adjustments'] = array_merge($service['adjustments'], $serviceAdjustments);
                    break;
            }
            
            $tempIndex++;
        }
        
        if ($claim !== null) {
            $claim['service_lines'][] = $service;
        }
        
        return $service;
    }
    
    /**
     * Parse DTM segment within service context
     */
    private function parseServiceDTM($segment, &$service) {
        $elements = $this->getSegmentElements($segment);
        
        if (count($elements) < 3) {
            return;
        }
        
        $dateQualifier = $elements[1];
        $date = $elements[2];
        
        switch ($dateQualifier) {
            case '472': // Service date
                $service['dates']['service'] = $date;
                break;
            case '150': // Service period start
                $service['dates']['start'] = $date;
                break;
            case '151': // Service period end
                $service['dates']['end'] = $date;
                break;
        }
    }
    
    /**
     * Parse PLB segment (Provider Level Balance)
     */
    private function parsePLB($segment) {
        $elements = $this->getSegmentElements($segment);
        
        if (count($elements) < 5) {
            $this->warnings[] = "PLB segment has insufficient elements";
            return;
        }
        
        $adjustment = [
            'provider_id' => $elements[1],
            'fiscal_period_date' => $elements[2],
            'adjustments' => []
        ];
        
        // PLB can have multiple adjustment reason sets
        for ($i = 3; $i < count($elements); $i += 2) {
            if (isset($elements[$i]) && isset($elements[$i + 1])) {
                $reasonComposite = explode(self::SUBELEMENT_SEPARATOR, $elements[$i]);
                $adjustment['adjustments'][] = [
                    'reason_code' => $reasonComposite[0],
                    'reference_id' => $reasonComposite[1] ?? '',
                    'amount' => floatval($elements[$i + 1])
                ];
            }
        }
        
        $this->providerAdjustments[] = $adjustment;
    }
    
    /**
     * Parse SE segment (Transaction Set Trailer)
     */
    private function parseSE($segment) {
        $elements = $this->getSegmentElements($segment);
        
        if (count($elements) < 3) {
            $this->warnings[] = "SE segment has insufficient elements";
            return;
        }
        
        $this->summary['segment_count'] = $elements[1];
        $this->summary['transaction_set_control_number'] = $elements[2];
    }
    
    /**
     * Parse GE segment (Functional Group Trailer)
     */
    private function parseGE($segment) {
        $elements = $this->getSegmentElements($segment);
        
        if (count($elements) < 3) {
            $this->warnings[] = "GE segment has insufficient elements";
            return;
        }
        
        $this->summary['transaction_sets_included'] = $elements[1];
        $this->summary['group_control_number'] = $elements[2];
    }
    
    /**
     * Parse IEA segment (Interchange Control Trailer)
     */
    private function parseIEA($segment) {
        $elements = $this->getSegmentElements($segment);
        
        if (count($elements) < 3) {
            $this->warnings[] = "IEA segment has insufficient elements";
            return;
        }
        
        $this->summary['functional_groups_included'] = $elements[1];
        $this->summary['interchange_control_number'] = $elements[2];
    }
    
    /**
     * Create payment batch record
     * 
     * @return int Batch ID
     */
    private function createPaymentBatch() {
        $stmt = $this->db->prepare("
            INSERT INTO payment_batches (
                batch_type,
                check_number,
                payment_date,
                payer_name,
                payer_id,
                total_amount,
                status,
                created_by,
                created_at
            ) VALUES (
                'EDI_835',
                :check_number,
                :payment_date,
                :payer_name,
                :payer_id,
                :total_amount,
                'processing',
                :user_id,
                NOW()
            )
        ");
        
        $stmt->execute([
            ':check_number' => $this->summary['check_or_eft_number'] ?? '',
            ':payment_date' => $this->formatDate($this->summary['check_issue_date'] ?? date('Ymd')),
            ':payer_name' => $this->payer['name'] ?? 'Maryland Medicaid',
            ':payer_id' => $this->payer['id'] ?? '',
            ':total_amount' => $this->summary['total_payment_amount'] ?? 0,
            ':user_id' => $_SESSION['user_id'] ?? 0
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Process individual claim payment
     * 
     * @param array $claim Parsed claim data
     * @param int $batchId Payment batch ID
     * @return array Processing result
     */
    private function processClaimPayment($claim, $batchId) {
        try {
            // Find the claim in the database
            $stmt = $this->db->prepare("
                SELECT id, client_id, total_amount, status 
                FROM billing_claims 
                WHERE claim_number = :claim_number
            ");
            $stmt->execute([':claim_number' => $claim['claim_number']]);
            $dbClaim = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$dbClaim) {
                $this->warnings[] = "Claim not found in database: " . $claim['claim_number'];
                return ['success' => false, 'error' => 'Claim not found'];
            }
            
            // Create payment record
            $paymentId = $this->createPaymentRecord($dbClaim['id'], $claim, $batchId);
            
            // Process adjustments
            $totalAdjustments = 0;
            foreach ($claim['adjustments'] as $adjustment) {
                $this->createAdjustmentRecord($paymentId, $adjustment, 'claim');
                $totalAdjustments += $adjustment['amount'];
            }
            
            // Process service line payments and adjustments
            foreach ($claim['service_lines'] as $service) {
                $servicePaymentId = $this->createServicePaymentRecord($paymentId, $service);
                
                foreach ($service['adjustments'] as $adjustment) {
                    $this->createAdjustmentRecord($servicePaymentId, $adjustment, 'service');
                    $totalAdjustments += $adjustment['amount'];
                }
            }
            
            // Update claim status based on payment
            $newStatus = $this->determineClaimStatus($claim);
            $this->updateClaimStatus($dbClaim['id'], $newStatus, $claim['payment_amount']);
            
            // Log the payment posting
            $this->logPaymentPosting($dbClaim['id'], $claim, $paymentId);
            
            return [
                'success' => true,
                'payment_id' => $paymentId,
                'payment_amount' => $claim['payment_amount'],
                'adjustment_amount' => $totalAdjustments
            ];
            
        } catch (\Exception $e) {
            $this->errors[] = "Error processing claim payment: " . $e->getMessage();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Create payment record
     */
    private function createPaymentRecord($claimId, $claimData, $batchId) {
        $stmt = $this->db->prepare("
            INSERT INTO claim_payments (
                claim_id,
                batch_id,
                payment_date,
                payment_amount,
                patient_responsibility,
                payer_claim_number,
                claim_status_code,
                created_at
            ) VALUES (
                :claim_id,
                :batch_id,
                :payment_date,
                :payment_amount,
                :patient_responsibility,
                :payer_claim_number,
                :claim_status_code,
                NOW()
            )
        ");
        
        $stmt->execute([
            ':claim_id' => $claimId,
            ':batch_id' => $batchId,
            ':payment_date' => $this->formatDate($this->summary['check_issue_date'] ?? date('Ymd')),
            ':payment_amount' => $claimData['payment_amount'],
            ':patient_responsibility' => $claimData['patient_responsibility'],
            ':payer_claim_number' => $claimData['payer_claim_control_number'],
            ':claim_status_code' => $claimData['claim_status_code']
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Create service payment record
     */
    private function createServicePaymentRecord($paymentId, $serviceData) {
        $stmt = $this->db->prepare("
            INSERT INTO service_payments (
                payment_id,
                procedure_code,
                modifiers,
                charge_amount,
                payment_amount,
                units_paid,
                service_date,
                created_at
            ) VALUES (
                :payment_id,
                :procedure_code,
                :modifiers,
                :charge_amount,
                :payment_amount,
                :units_paid,
                :service_date,
                NOW()
            )
        ");
        
        $stmt->execute([
            ':payment_id' => $paymentId,
            ':procedure_code' => $serviceData['procedure_code'],
            ':modifiers' => implode(',', $serviceData['modifiers']),
            ':charge_amount' => $serviceData['charge_amount'],
            ':payment_amount' => $serviceData['payment_amount'],
            ':units_paid' => $serviceData['units_paid'],
            ':service_date' => $this->formatDate($serviceData['dates']['service'] ?? null)
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Create adjustment record
     */
    private function createAdjustmentRecord($paymentId, $adjustmentData, $level = 'claim') {
        $stmt = $this->db->prepare("
            INSERT INTO payment_adjustments (
                payment_id,
                adjustment_level,
                group_code,
                reason_code,
                amount,
                quantity,
                created_at
            ) VALUES (
                :payment_id,
                :adjustment_level,
                :group_code,
                :reason_code,
                :amount,
                :quantity,
                NOW()
            )
        ");
        
        $stmt->execute([
            ':payment_id' => $paymentId,
            ':adjustment_level' => $level,
            ':group_code' => $adjustmentData['group_code'],
            ':reason_code' => $adjustmentData['reason_code'],
            ':amount' => $adjustmentData['amount'],
            ':quantity' => $adjustmentData['quantity']
        ]);
    }
    
    /**
     * Process provider level adjustments
     */
    private function processProviderAdjustments($batchId) {
        foreach ($this->providerAdjustments as $plb) {
            $stmt = $this->db->prepare("
                INSERT INTO provider_adjustments (
                    batch_id,
                    provider_id,
                    fiscal_period_date,
                    created_at
                ) VALUES (
                    :batch_id,
                    :provider_id,
                    :fiscal_period_date,
                    NOW()
                )
            ");
            
            $stmt->execute([
                ':batch_id' => $batchId,
                ':provider_id' => $plb['provider_id'],
                ':fiscal_period_date' => $this->formatDate($plb['fiscal_period_date'])
            ]);
            
            $plbId = $this->db->lastInsertId();
            
            foreach ($plb['adjustments'] as $adj) {
                $stmt = $this->db->prepare("
                    INSERT INTO provider_adjustment_details (
                        provider_adjustment_id,
                        reason_code,
                        reference_id,
                        amount,
                        created_at
                    ) VALUES (
                        :plb_id,
                        :reason_code,
                        :reference_id,
                        :amount,
                        NOW()
                    )
                ");
                
                $stmt->execute([
                    ':plb_id' => $plbId,
                    ':reason_code' => $adj['reason_code'],
                    ':reference_id' => $adj['reference_id'],
                    ':amount' => $adj['amount']
                ]);
            }
        }
    }
    
    /**
     * Update batch totals
     */
    private function updateBatchTotals($batchId, $claimCount, $totalPosted, $totalAdjustments) {
        $stmt = $this->db->prepare("
            UPDATE payment_batches 
            SET claim_count = :claim_count,
                posted_amount = :posted_amount,
                adjustment_amount = :adjustment_amount,
                status = 'completed',
                completed_at = NOW()
            WHERE id = :batch_id
        ");
        
        $stmt->execute([
            ':batch_id' => $batchId,
            ':claim_count' => $claimCount,
            ':posted_amount' => $totalPosted,
            ':adjustment_amount' => $totalAdjustments
        ]);
    }
    
    /**
     * Determine claim status based on payment information
     */
    private function determineClaimStatus($claim) {
        if ($claim['claim_status_code'] == '4') {
            return 'denied';
        } elseif ($claim['claim_status_code'] == '22') {
            return 'reversed';
        } elseif ($claim['payment_amount'] == 0) {
            return 'denied';
        } elseif ($claim['payment_amount'] < $claim['total_charge']) {
            return 'partial_payment';
        } else {
            return 'paid';
        }
    }
    
    /**
     * Update claim status
     */
    private function updateClaimStatus($claimId, $status, $paidAmount) {
        $stmt = $this->db->prepare("
            UPDATE billing_claims 
            SET status = :status,
                paid_amount = paid_amount + :paid_amount,
                last_payment_date = NOW(),
                updated_at = NOW()
            WHERE id = :claim_id
        ");
        
        $stmt->execute([
            ':claim_id' => $claimId,
            ':status' => $status,
            ':paid_amount' => $paidAmount
        ]);
    }
    
    /**
     * Log payment posting
     */
    private function logPaymentPosting($claimId, $claimData, $paymentId) {
        $this->log(sprintf(
            "Payment posted - Claim: %s, Amount: $%.2f, Status: %s, Payment ID: %d",
            $claimData['claim_number'],
            $claimData['payment_amount'],
            $claimData['claim_status_code'],
            $paymentId
        ));
    }
    
    /**
     * Format date from EDI format to MySQL format
     */
    private function formatDate($ediDate) {
        if (empty($ediDate) || strlen($ediDate) < 8) {
            return null;
        }
        
        $year = substr($ediDate, 0, 4);
        $month = substr($ediDate, 4, 2);
        $day = substr($ediDate, 6, 2);
        
        return "$year-$month-$day";
    }
    
    /**
     * Get current segment
     */
    private function getCurrentSegment() {
        if ($this->currentIndex >= count($this->segments)) {
            return null;
        }
        return trim($this->segments[$this->currentIndex]);
    }
    
    /**
     * Get segment type
     */
    private function getSegmentType($segment) {
        $elements = explode(self::ELEMENT_SEPARATOR, $segment);
        return $elements[0] ?? '';
    }
    
    /**
     * Get segment elements
     */
    private function getSegmentElements($segment) {
        return explode(self::ELEMENT_SEPARATOR, $segment);
    }
    
    /**
     * Reset parser state
     */
    private function reset() {
        $this->segments = [];
        $this->currentIndex = 0;
        $this->errors = [];
        $this->warnings = [];
        $this->header = [];
        $this->payer = [];
        $this->payee = [];
        $this->claims = [];
        $this->providerAdjustments = [];
        $this->summary = [];
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
     * Get warnings
     * 
     * @return array
     */
    public function getWarnings() {
        return $this->warnings;
    }
    
    /**
     * Get parsed data
     * 
     * @return array
     */
    public function getParsedData() {
        return [
            'header' => $this->header,
            'payer' => $this->payer,
            'payee' => $this->payee,
            'claims' => $this->claims,
            'provider_adjustments' => $this->providerAdjustments,
            'summary' => $this->summary
        ];
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
                    'transaction_type' => '835',
                    'check_number' => $this->summary['check_or_eft_number'] ?? '',
                    'claim_count' => count($this->claims)
                ])
            ]);
        } catch (\Exception $e) {
            // Silently fail logging
        }
    }
    
    /**
     * Validate Maryland Medicaid specific requirements
     * 
     * @return array Validation result
     */
    public function validateMarylandMedicaidFormat() {
        $errors = [];
        
        // Check payer identification
        if (empty($this->payer['id']) || !in_array($this->payer['id'], ['MDMEDICAID', '64079'])) {
            $errors[] = "Invalid Maryland Medicaid payer ID";
        }
        
        // Validate autism waiver service codes
        foreach ($this->claims as $claim) {
            foreach ($claim['service_lines'] as $service) {
                $validCodes = ['H2019', 'H2014', 'T1027', 'H2015', 'S5111'];
                if (!in_array($service['procedure_code'], $validCodes)) {
                    $errors[] = "Invalid autism waiver service code: " . $service['procedure_code'];
                }
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}