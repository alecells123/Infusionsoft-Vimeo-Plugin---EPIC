<?php
/**
 * @method Object Oriented PHP SDK for Infusionsoft
 * @CreatedBy Justin Morris on 09-10-08
 * @UpdatedBy Michael Fairchild, Alec Ellsworth
 * @Updated 02/12/2024
 * @ifiSDKVersion 1.8.7
 */

class ifiSDKException extends Exception {}

class ifiSDK {
    private $key;
    private $debug;
    public $logname = '';
    public $loggingEnabled = 0;

    private function sdk_debug_log($message, $data = null) {
        if ($this->debug && defined('WP_DEBUG') && WP_DEBUG) {
            $debug_info = json_encode([
                'time' => date('Y-m-d H:i:s'),
                'message' => $message,
                'data' => $data
            ], JSON_PRETTY_PRINT);
            error_log("=== INFUSIONSOFT SDK DEBUG === " . $debug_info);
        }
    }

    private function makeApiCall($service, $params = []) {
        try {
            $curl = curl_init();
            
            // Build XML request
            $xml_request = '<?xml version="1.0"?>
            <methodCall>
              <methodName>' . $service . '</methodName>
              <params>';
            
            foreach ($params as $param) {
                $xml_request .= '
                <param>
                  <value>' . htmlspecialchars($param) . '</value>
                </param>';
            }
            
            $xml_request .= '
              </params>
            </methodCall>';

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.infusionsoft.com/crm/xmlrpc',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $xml_request,
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Bearer ' . $this->key,
                    'Content-Type: text/xml'
                )
            ));

            $response = curl_exec($curl);
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            if ($http_code !== 200) {
                throw new ifiSDKException("API request failed: HTTP $http_code");
            }

            return true;

        } catch (Exception $e) {
            if ($this->debug) {
                error_log("Infusionsoft API Error: " . $e->getMessage());
            }
            throw new ifiSDKException($e->getMessage());
        } finally {
            if (isset($curl) && is_resource($curl)) {
                curl_close($curl);
            }
        }
    }

    /**
     * @method cfgCon
     * @description Creates and tests the API Connection
     * @param string $name - Application Name
     * @param string $key - OAuth Bearer Token
     * @param string $dbOn - Debug Mode
     * @return bool
     * @throws ifiSDKException
     */
    public function cfgCon($name, $key = "", $dbOn = "on") {
        // Get settings from WordPress options
        $options = get_option('iv_settings', []);
        $this->key = !empty($key) ? $key : ($options['api_key'] ?? '');
        $this->debug = $dbOn;
        
        if (empty($this->key)) {
            throw new ifiSDKException("No API token provided");
        }
        
        $this->sdk_debug_log('Starting connection', [
            'key_length' => strlen($this->key)
        ]);

        try {
            return $this->makeApiCall("DataService.getAppSetting", ["Application", "enabled"]);
        } catch (Exception $e) {
            throw new ifiSDKException("Connection Failed: " . $e->getMessage());
        }
    }

    /**
     * @method vendorCon
     * @description Connect using OAuth Bearer token
     * @param string $name - Application Name
     * @param string $key - OAuth Bearer Token
     * @param string $dbOn - Debug Mode
     * @return bool
     * @throws ifiSDKException
     */
    public function vendorCon($name, $key = "", $dbOn = "on") {
        $this->debug = $dbOn;
        
        // Get settings from WordPress options if no key provided
        if (empty($key)) {
            $options = get_option('iv_settings', []);
            $this->key = $options['api_key'] ?? '';
        } else {
            $this->key = $key;
        }

        if (empty($this->key)) {
            throw new ifiSDKException("No API token provided");
        }

        try {
            // Test the connection
            $connected = $this->makeApiCall("DataService.getAppSetting", ["Application", "enabled"]);
            return true;
        } catch (Exception $e) {
            throw new ifiSDKException("Connection Failed: " . $e->getMessage());
        }
    }

    /**
     * @method echo
     * @description Worthless public function, used to validate a connection
     * @param string $txt
     * @return int|mixed|string
     */
    public function appEcho($txt)
    {
        return $this->makeApiCall("DataService.echo", [$txt]);
    }

    /**
     * @service Affiliate Program Service
     */

    /**
     * @method getAffiliatesByProgram
     * @description Gets affiliates for a program
     */
    public function getAffiliatesByProgram($programId)
    {
        return $this->makeApiCall("AffiliateProgramService.getAffiliatesByProgram", [(int)$programId]);
    }

    /**
     * @method getProgramsForAffiliate
     * @description Gets programs for an affiliate
     */
    public function getProgramsForAffiliate($affiliateId)
    {
        return $this->makeApiCall("AffiliateProgramService.getProgramsForAffiliate", [(int)$affiliateId]);
    }

    /**
     * @method getAffiliatePrograms
     * @description Gets a list of all of the Affiliate Programs that are in the application.
     * @return int|mixed|string
     */
    public function getAffiliatePrograms()
    {
        return $this->makeApiCall("AffiliateProgramService.getAffiliatePrograms", []);
    }

    /**
     * @method getResourcesForAffiliateProgram
     * @description Gets a list of all of the resources that are associated to the Affiliate Program specified.
     * @param int $programId
     * @return array
     */
    public function getResourcesForAffiliateProgram($programId)
    {
        return $this->makeApiCall("AffiliateProgramService.getResourcesForAffiliateProgram", [(int)$programId]);
    }

    /**
     * @service Affiliate Service
     */

    /**
     * @method affClawbacks
     * @description returns all clawbacks in a date range
     */
    public function affClawbacks($affiliateId, $startDate, $endDate)
    {
        return $this->makeApiCall("APIAffiliateService.affClawbacks", [
            (int)$affiliateId,
            $startDate,
            $endDate
        ]);
    }

    /**
     * @method affCommissions
     * @description returns all commissions in a date range
     */
    public function affCommissions($affiliateId, $startDate, $endDate)
    {
        return $this->makeApiCall("APIAffiliateService.affCommissions", [
            (int)$affiliateId,
            $startDate,
            $endDate
        ]);
    }

    /**
     * @method affPayouts
     * @description returns all affiliate payouts in a date range
     */
    public function affPayouts($affId, $startDate, $endDate)
    {
        return $this->makeApiCall("APIAffiliateService.affPayouts", [
            (int)$affId,
            $startDate,
            $endDate
        ]);
    }

    /**
     * @method affRunningTotals
     * @description Returns affiliate totals
     */
    public function affRunningTotals($affList)
    {
        return $this->makeApiCall("APIAffiliateService.affRunningTotals", [
            $affList
        ]);
    }

    /**
     * @method affSummary
     * @description returns how much the specified affiliates are owed
     */
    public function affSummary($affList, $startDate, $endDate)
    {
        return $this->makeApiCall("APIAffiliateService.affSummary", [
            $affList,
            $startDate,
            $endDate
        ]);
    }

    /**
     * @method getRedirectLinksForAffiliate
     * @description returns redirect links for affiliate specified
     */
    public function getRedirectLinksForAffiliate($affiliateId)
    {
        return $this->makeApiCall("AffiliateService.getRedirectLinksForAffiliate", [
            (int)$affiliateId
        ]);
    }

    /**
     * @service Contact Service
     */

    public function addCon($cMap, $optReason = "") {
        $conID = $this->makeApiCall("ContactService.add", [$cMap]);
        if (!empty($cMap['Email'])) {
            $this->optIn($cMap['Email'], $optReason ?: "API Opt In");
        }
        return $conID;
    }

    public function updateCon($cid, $cMap) {
        return $this->makeApiCall("ContactService.update", [(int)$cid, $cMap]);
    }

    public function mergeCon($cid, $dcid) {
        return $this->makeApiCall("ContactService.merge", [(int)$cid, (int)$dcid]);
    }

    public function findByEmail($email, $fields) {
        return $this->makeApiCall("ContactService.findByEmail", [$email, $fields]);
    }

    public function loadCon($contactId, $fields) {
        return $this->makeApiCall("ContactService.load", [(int)$contactId, $fields]);
    }

    public function grpAssign($contactId, $groupId) {
        return $this->makeApiCall("ContactService.addToGroup", [(int)$contactId, (int)$groupId]);
    }

    public function grpRemove($contactId, $groupId) {
        return $this->makeApiCall("ContactService.removeFromGroup", [(int)$contactId, (int)$groupId]);
    }

    public function resumeCampaignForContact($cid, $sequenceId) {
        return $this->makeApiCall("ContactService.resumeCampaignForContact", [
            (int)$cid,
            (int)$sequenceId
        ]);
    }

    public function campAssign($cid, $campId) {
        return $this->makeApiCall("ContactService.addToCampaign", [
            (int)$cid,
            (int)$campId
        ]);
    }

    public function getNextCampaignStep($cid, $campId) {
        return $this->makeApiCall("ContactService.getNextCampaignStep", [
            (int)$cid,
            (int)$campId
        ]);
    }

    public function getCampaigneeStepDetails($cid, $stepId) {
        return $this->makeApiCall("ContactService.getCampaigneeStepDetails", [
            (int)$cid,
            (int)$stepId
        ]);
    }

    public function rescheduleCampaignStep($cidList, $campId) {
        return $this->makeApiCall("ContactService.rescheduleCampaignStep", [
            $cidList,
            (int)$campId
        ]);
    }

    public function campRemove($cid, $campId) {
        return $this->makeApiCall("ContactService.removeFromCampaign", [
            (int)$cid,
            (int)$campId
        ]);
    }

    public function campPause($cid, $campId) {
        return $this->makeApiCall("ContactService.pauseCampaign", [
            (int)$cid,
            (int)$campId
        ]);
    }

    public function runAS($cid, $aid) {
        return $this->makeApiCall("ContactService.runActionSequence", [
            (int)$cid,
            (int)$aid
        ]);
    }

    public function applyActivityHistoryTemplate($contactId, $historyId, $userId) {
        return $this->makeApiCall("ContactService.applyActivityHistoryTemplate", [
            (int)$contactId,
            (int)$historyId,
            (int)$userId
        ]);
    }

    public function getActivityHistoryTemplateMap() {
        return $this->makeApiCall("ContactService.getActivityHistoryTemplateMap", []);
    }

    public function addWithDupCheck($cMap, $checkType) {
        return $this->makeApiCall("ContactService.addWithDupCheck", [$cMap, $checkType]);
    }

    /**
     * @service Credit Card Submission Service
     */

    /**
     * @method requestSubmissionToken
     * @description gets a token, which is needed to POST a credit card to the application
     * @param int $contactId
     * @param string $successUrl
     * @param string $failureUrl
     * @return string
     */
    public function requestCcSubmissionToken($contactId, $successUrl, $failureUrl)
    {
        return $this->makeApiCall("CreditCardSubmissionService.requestSubmissionToken", [
            (int)$contactId,
            (string)$successUrl,
            (string)$failureUrl
        ]);
    }

    /**
     * @method requestCreditCardId
     * @description retrieves credit card details (CC number not included) that have been posted to the app
     * @param $token
     * @return array
     */
    public function requestCreditCardId($token)
    {
        return $this->makeApiCall("CreditCardSubmissionService.requestCreditCardId", [$token]);
    }

    /**
     * @service Data Service
     */

    /**
     * @method getAppSetting
     * @description gets an app setting
     * @param string $module
     * @param string $setting
     * @return int|mixed|string
     */
    public function dsGetSetting($module, $setting)
    {
        return $this->makeApiCall("DataService.getAppSetting", [$module, $setting]);
    }

    /**
     * @method add
     * @description Add a record to a table
     */
    public function dsAdd($tableName, $data)
    {
        return $this->makeApiCall("DataService.add", [$tableName, $data]);
    }

    /**
     * @method dsAddWithImage
     * @description Add a record to a table that includes an image
     * @param string $tName
     * @param array $iMap
     * @return int
     */
    public function dsAddWithImage($tName, $iMap)
    {
        return $this->makeApiCall("DataService.add", [$tName, $iMap]);
    }

    /**
     * @method delete
     * @description delete a record from Infusionsoft
     * @param string $tName
     * @param int $id
     * @return bool
     */
    public function dsDelete($tName, $id)
    {
        return $this->makeApiCall("DataService.delete", [$tName, (int)$id]);
    }

    /**
     * @method update
     * @description Update a record in any table
     */
    public function dsUpdate($tName, $id, $iMap)
    {
        return $this->makeApiCall("DataService.update", [$tName, (int)$id, $iMap]);
    }

    /**
     * @method dsUpdateWithImage
     * @description Update a record in any table with an image
     * @param string $tName
     * @param int $id
     * @param array $iMap
     * @return int
     */
    public function dsUpdateWithImage($tName, $id, $iMap)
    {
        return $this->makeApiCall("DataService.updateWithImage", [
            $tName,
            (int)$id,
            $iMap
        ]);
    }

    /**
     * @method load
     * @description Load a record from any table
     */
    public function dsLoad($tName, $id, $rFields)
    {
        return $this->makeApiCall("DataService.load", [
            $tName,
            (int)$id,
            $rFields
        ]);
    }

    /**
     * @method findByField
     * @description finds records by searching a specific field
     */
    public function dsFind($tName, $limit, $page, $field, $value, $rFields)
    {
        return $this->makeApiCall("DataService.findByField", [
            $tName,
            (int)$limit,
            (int)$page,
            $field,
            $value,
            $rFields
        ]);
    }

    /**
     * @method query
     * @description Finds records based on query
     */
    public function dsQuery($tableName, $limit, $page, $queryData, $selectedFields)
    {
        return $this->makeApiCall("DataService.query", [
            $tableName,
            (int)$limit,
            (int)$page,
            $queryData,
            $selectedFields
        ]);
    }

    /**
     * @method queryWithOrderBy
     * @description Finds records based on query with option to sort
     * @param string $tName
     * @param int $limit
     * @param int $page
     * @param array $query
     * @param array $rFields
     * @param string $orderByField
     * @param bool $ascending
     * @return array
     */
    public function dsQueryOrderBy($tName, $limit, $page, $query, $rFields, $orderByField, $ascending = TRUE)
    {
        return $this->makeApiCall("DataService.queryOrderBy", [
            $tName,
            (int)$limit,
            (int)$page,
            $query,
            $rFields,
            $orderByField,
            (bool)$ascending
        ]);
    }

    /**
     * @method DataService.Count
     * @description Gets record count based on query
     */
    public function dsCount($tName, $query)
    {
        return $this->makeApiCall("DataService.count", [
            $tName,
            $query
        ]);
    }

    /**
     * @method addCustomField
     * @description adds a custom field
     */
    public function addCustomField($context, $displayName, $dataType, $headerID)
    {
        return $this->makeApiCall("DataService.addCustomField", [
            $context,
            $displayName,
            $dataType,
            (int)$headerID
        ]);
    }

    /**
     * @method authenticateUser
     * @description Authenticates a user account in Infusionsoft
     */
    public function authenticateUser($userName, $password)
    {
        return $this->makeApiCall("DataService.authenticateUser", [
            $userName,
            strtolower(md5($password))
        ]);
    }

    /**
     * @method updateCustomField
     * @description update a custom field
     */
    public function updateCustomField($fieldId, $fieldValues)
    {
        return $this->makeApiCall("DataService.updateCustomField", [
            (int)$fieldId,
            $fieldValues
        ]);
    }

    /**
     * @service Discount Service
     */

    /**
     * @method addFreeTrial
     * @description creates a subscription free trial for the shopping cart
     */
    public function addFreeTrial($name, $description, $freeTrialDays, $hidePrice, $subscriptionPlanId)
    {
        return $this->makeApiCall("DiscountService.addFreeTrial", [
            (string)$name,
            (string)$description,
            (int)$freeTrialDays,
            (int)$hidePrice,
            (int)$subscriptionPlanId
        ]);
    }

    /**
     * @method getFreeTrial
     * @description retrieves the details on the given free trial
     */
    public function getFreeTrial($trialId)
    {
        return $this->makeApiCall("DiscountService.getFreeTrial", [
            (int)$trialId
        ]);
    }

    /**
     * @method addOrderTotalDiscount
     * @description creates an order total discount for the shopping cart
     */
    public function addOrderTotalDiscount($name, $description, $applyDiscountToCommission, $percentOrAmt, $amt, $payType)
    {
        return $this->makeApiCall("DiscountService.addOrderTotalDiscount", [
            (string)$name,
            (string)$description,
            (int)$applyDiscountToCommission,
            (int)$percentOrAmt,
            $amt,
            $payType
        ]);
    }

    /**
     * @method getOrderTotalDiscount
     * @description retrieves the details on the given order total discount
     */
    public function getOrderTotalDiscount($id)
    {
        return $this->makeApiCall("DiscountService.getOrderTotalDiscount", [
            (int)$id
        ]);
    }

    /**
     * @method addCategoryDiscount
     * @description creates a product category discount for the shopping cart
     */
    public function addCategoryDiscount($name, $description, $applyDiscountToCommission, $amt)
    {
        return $this->makeApiCall("DiscountService.addCategoryDiscount", [
            (string)$name,
            (string)$description,
            (int)$applyDiscountToCommission,
            $amt
        ]);
    }

    /**
     * @method getCategoryDiscount
     * @description retrieves the details on the Category discount
     */
    public function getCategoryDiscount($id)
    {
        return $this->makeApiCall("DiscountService.getCategoryDiscount", [
            (int)$id
        ]);
    }

    /**
     * @method addCategoryAssignmentToCategoryDiscount
     * @description assigns a product category to a particular category discount
     */
    public function addCategoryAssignmentToCategoryDiscount($categoryDiscountId, $productCategoryId)
    {
        return $this->makeApiCall("DiscountService.addCategoryAssignmentToCategoryDiscount", [
            (int)$categoryDiscountId,
            (int)$productCategoryId
        ]);
    }

    /**
     * @method getCategoryAssignmentsForCategoryDiscount
     * @description retrieves the product categories for the given category discount
     */
    public function getCategoryAssignmentsForCategoryDiscount($id)
    {
        return $this->makeApiCall("DiscountService.getCategoryAssignmentsForCategoryDiscount", [
            (int)$id
        ]);
    }

    /**
     * @method addProductTotalDiscount
     * @description creates a product total discount for the shopping cart
     */
    public function addProductTotalDiscount($name, $description, $applyDiscountToCommission, $productId, $percentOrAmt, $amt)
    {
        return $this->makeApiCall("DiscountService.addProductTotalDiscount", [
            (string)$name,
            (string)$description,
            (int)$applyDiscountToCommission,
            (int)$productId,
            (int)$percentOrAmt,
            $amt
        ]);
    }

    /**
     * @method getProductTotalDiscount
     * @description retrieves the details on the given product total discount
     */
    public function getProductTotalDiscount($id)
    {
        return $this->makeApiCall("DiscountService.getProductTotalDiscount", [
            (int)$id
        ]);
    }

    /**
     * @method addShippingTotalDiscount
     * @description creates a shipping total discount for the shopping cart
     */
    public function addShippingTotalDiscount($name, $description, $applyDiscountToCommission, $percentOrAmt, $amt)
    {
        return $this->makeApiCall("DiscountService.addShippingTotalDiscount", [
            (string)$name,
            (string)$description,
            (int)$applyDiscountToCommission,
            (int)$percentOrAmt,
            $amt
        ]);
    }

    /**
     * @method getShippingTotalDiscount
     * @description retrieves the details on the given shipping total discount
     */
    public function getShippingTotalDiscount($id)
    {
        return $this->makeApiCall("DiscountService.getShippingTotalDiscount", [
            (int)$id
        ]);
    }

    /**
     * @service API Email Service
     */

    /**
     * @method attachEmail
     * @description attachs an email to a contacts email history
     * @param int $cId
     * @param string $fromName
     * @param string $fromAddress
     * @param string $toAddress
     * @param string $ccAddresses
     * @param string $bccAddresses
     * @param string $contentType
     * @param string $subject
     * @param string $htmlBody
     * @param string $txtBody
     * @param string $header
     * @param date $strRecvdDate
     * @param date $strSentDate
     * @param int $emailSentType
     * @return bool
     */
    public function attachEmail($cId, $fromName, $fromAddress, $toAddress, $ccAddresses,
                                $bccAddresses, $contentType, $subject, $htmlBody, $txtBody,
                                $header, $strRecvdDate, $strSentDate, $emailSentType = 1)
    {
        return $this->makeApiCall("APIEmailService.attachEmail", [
            (int)$cId,
            $fromName,
            $fromAddress,
            $toAddress,
            $ccAddresses,
            $bccAddresses,
            $contentType,
            $subject,
            $htmlBody,
            $txtBody,
            $header,
            $strRecvdDate,
            $strSentDate,
            $emailSentType
        ]);
    }

    /**
     * @method getAvailableMergeFields
     * @description gets a list of all available merge fields
     * @param string $mergeContext
     * @return array
     */
    public function getAvailableMergeFields($mergeContext)
    {
        return $this->makeApiCall("APIEmailService.getAvailableMergeFields", [
            $mergeContext
        ]);
    }

    /**
     * @method sendEmail
     * @description send an email to a list of contacts
     * @param array $conList
     * @param string $fromAddress
     * @param string $toAddress
     * @param string $ccAddresses
     * @param string $bccAddresses
     * @param string $contentType
     * @param string $subject
     * @param string $htmlBody
     * @param string $txtBody
     * @return bool
     */
    public function sendEmail($conList, $fromAddress, $toAddress, $ccAddresses, $bccAddresses, $contentType, $subject, $htmlBody, $txtBody)
    {
        return $this->makeApiCall("APIEmailService.sendEmail", [
            $conList,
            $fromAddress,
            $toAddress,
            $ccAddresses,
            $bccAddresses,
            $contentType,
            $subject,
            $htmlBody,
            $txtBody
        ]);

        return $this->methodCaller("APIEmailService.sendEmail", $carray);
    }

    /**
     * @method sendTemplate
     * @description sends a template to a list of contacts
     * @note uses APIEmailService.sendEmail with different parameters
     * @param array $conList
     * @param int $template
     * @return bool
     */
    public function sendTemplate($conList, $template)
    {
        return $this->makeApiCall("APIEmailService.sendEmail", [
            $conList,
            $template
        ]);
    }

    /**
     * @note THIS IS DEPRECATED - USE addEmailTemplate instead!
     * @method createEmailTemplate
     * @description Creates a legacy Email Template
     * @param string $title
     * @param int $userID
     * @param string $fromAddress
     * @param string $toAddress
     * @param string $ccAddresses
     * @param string $bccAddresses
     * @param string $contentType
     * @param string $subject
     * @param string $htmlBody
     * @param string $txtBody
     * @return int
     */
    public function createEmailTemplate($title, $userID, $fromAddress, $toAddress, $ccAddresses, $bccAddresses, $contentType, $subject, $htmlBody,
                                        $txtBody)
    {
        return $this->makeApiCall("APIEmailService.addEmailTemplate", [
            $title,
            $category = '',
            $fromAddress,
            $toAddress,
            $ccAddresses,
            $bccAddresses,
            $subject,
            $txtBody,
            $htmlBody,
            $contentType,
            $mergeContext = 'Contact'
        ]);
    }

    /**
     * @method addEmailTemplate
     * @description creates an Email Template
     * @param string $title
     * @param string $category
     * @param string $fromAddress
     * @param string $toAddress
     * @param string $ccAddresses
     * @param string $bccAddresses
     * @param string $subject
     * @param string $txtBody
     * @param string $htmlBody
     * @param string $contentType
     * @param string $mergeContext
     * @return int
     */
    public function addEmailTemplate($title, $category, $fromAddress, $toAddress, $ccAddresses, $bccAddresses, $subject, $txtBody, $htmlBody, $contentType, $mergeContext)
    {
        return $this->makeApiCall("APIEmailService.addEmailTemplate", [
            $title,
            $category,
            $fromAddress,
            $toAddress,
            $ccAddresses,
            $bccAddresses,
            $subject,
            $txtBody,
            $htmlBody,
            $contentType,
            $mergeContext
        ]);
    }

    /**
     * @method getEmailTemplate
     * @description get the HTML of an email template
     * @param int $templateId
     * @return array
     */
    public function getEmailTemplate($templateId)
    {
        return $this->makeApiCall("APIEmailService.getEmailTemplate", [
            (int)$templateId
        ]);
    }

    /**
     * @method updateEmailTemplate
     * @description Update an Email template
     * @param int $templateID
     * @param string $title
     * @param string $categories
     * @param string $fromAddress
     * @param string $toAddress
     * @param string $ccAddress
     * @param string $bccAddress
     * @param string $subject
     * @param string $textBody
     * @param string $htmlBody
     * @param string $contentType
     * @param string $mergeContext
     * @return bool
     */
    public function updateEmailTemplate($templateID, $title, $categories, $fromAddress, $toAddress, $ccAddress, $bccAddress, $subject, $textBody, $htmlBody, $contentType, $mergeContext)
    {
        return $this->makeApiCall("APIEmailService.updateEmailTemplate", [
            (int)$templateID,
            $title,
            $categories,
            $fromAddress,
            $toAddress,
            $ccAddress,
            $bccAddress,
            $subject,
            $textBody,
            $htmlBody,
            $contentType,
            $mergeContext
        ]);
    }

    /**
     * @method getOptStatus
     * @description get the Opt status of an email
     * @param string $email
     * @return int
     */
    public function optStatus($email)
    {
        return $this->makeApiCall("APIEmailService.getOptStatus", [
            $email
        ]);
    }

    /**
     * @method optIn
     * @description Opts an email in to allow emails to be sent to them
     * @note  Opt-In will only work on "non-marketable contacts not opted out people
     * @param string $email
     * @param string $reason
     * @return bool
     */
    public function optIn($email, $reason = 'Contact Was Opted In through the API')
    {
        return $this->makeApiCall("APIEmailService.optIn", [
            $email,
            $reason
        ]);
    }

    /**
     * @method optOut
     * @description Opts an email out. Emails will not be sent to them anymore
     * @param string $email
     * @param string $reason
     * @return bool
     */
    public function optOut($email, $reason = 'Contact Was Opted Out through the API')
    {
        return $this->makeApiCall("APIEmailService.optOut", [
            $email,
            $reason
        ]);
    }

    /**
     * @method uploadFile
     * @description Upload a file to Infusionsoft
     * @param string $fileName
     * @param string $base64Enc
     * @param int $cid
     * @return int|mixed|string
     */
    public function uploadFile($fileName, $base64Enc, $cid = 0)
    {
        $result = 0;
        if ($cid == 0) {
            return $this->makeApiCall("FileService.uploadFile", [
                $fileName,
                $base64Enc
            ]);
        } else {
            return $this->makeApiCall("FileService.uploadFile", [
                (int)$cid,
                $fileName,
                $base64Enc
            ]);
        }
    }

    /**
     * @method replaceFile
     * @description replaces an existing file
     */
    public function replaceFile($fileId, $base64Enc)
    {
        return $this->makeApiCall("FileService.replaceFile", [
            (int)$fileId,
            $base64Enc
        ]);
    }

    /**
     * @method renameFile
     * @description renames an existing file
     */
    public function renameFile($fileId, $fileName)
    {
        return $this->makeApiCall("FileService.renameFile", [
            (int)$fileId,
            $fileName
        ]);
    }

    /**
     * @method getDownloadUrl
     * @description gets download url for public files
     */
    public function getDownloadUrl($fileId)
    {
        return $this->makeApiCall("FileService.getDownloadUrl", [
            (int)$fileId
        ]);
    }

    /**
     * @service Funnel Service
     */

    /**
     * @method achieveGoal
     * @description achieves an api goal inside of the Campaign Builder
     */
    public function achieveGoal($integration, $callName, $contactId)
    {
        return $this->makeApiCall("FunnelService.achieveGoal", [
            (string)$integration,
            (string)$callName,
            (int)$contactId
        ]);
    }

    /**
     * @service Invoice Service
     */

    /**
     * @method deleteInvoice
     * @description deletes an invoice
     * @param int $Id
     * @return bool
     */
    public function deleteInvoice($Id)
    {
        return $this->makeApiCall("InvoiceService.deleteInvoice", [
            (int)$Id
        ]);
    }

    /**
     * @method deleteSubscriptioin
     * @description Delete a Subscription created through the API
     * @param $Id
     * @return bool
     */
    public function deleteSubscription($Id)
    {
        return $this->makeApiCall("InvoiceService.deleteSubscription", [
            (int)$Id
        ]);
    }

    /**
     * @method setInvoiceSyncStatus
     * @description sets the sync status column on the Invoice table
     */
    public function setInvoiceSyncStatus($invoiceId, $syncStatus)
    {
        return $this->makeApiCall("InvoiceService.setInvoiceSyncStatus", [
            (int)$invoiceId,
            $syncStatus
        ]);
    }

    /**
     * @method setPaymentSyncStatus
     * @description sets the sync status column on the Payment table
     */
    public function setPaymentSyncStatus($paymentId, $syncStatus)
    {
        return $this->makeApiCall("InvoiceService.setPaymentSyncStatus", [
            (int)$paymentId,
            $syncStatus
        ]);
    }

    /**
     * @method getPluginStatus
     * @description Tells if the Ecommerce plugin is enabled
     */
    public function getPluginStatus($className)
    {
        return $this->makeApiCall("InvoiceService.getPluginStatus", [
            $className
        ]);
    }

    /**
     * @method manualPmt
     * @description add a manual payment to an invoice
     */
    public function manualPmt($invoiceId, $amt, $paymentDate, $paymentType, $paymentDescription, $bypassCommissions)
    {
        return $this->makeApiCall("InvoiceService.addManualPayment", [
            (int)$invoiceId,
            $amt,
            $paymentDate,
            $paymentType,
            $paymentDescription,
            (boolean)$bypassCommissions
        ]);
    }

    /**
     * @method addOrderCommissionOverride
     * @description Override Order Commissions
     * @param int $invId
     * @param int $affId
     * @param int $prodId
     * @param int $percentage
     * @param double $amt
     * @param int $payType
     * @param string $desc
     * @param date $date
     * @return bool
     */
    public function commOverride($invId, $affId, $prodId, $percentage, $amt, $payType, $desc, $date)
    {
        return $this->makeApiCall("InvoiceService.addOrderCommissionOverride", [
            (int)$invId,
            (int)$affId,
            (int)$prodId,
            $percentage,
            $amt,
            $payType,
            $desc,
            $date
        ]);
    }

    /**
     * @method addOrderItem
     * @description add a line item to an order
     * @param int $ordId
     * @param int $prodId
     * @param int $type
     * @paramOption 1 Shipping
     * @paramOption 2 Tax
     * @paramOption 3 Service & Misc
     * @paramOption 4 Product
     * @paramOption 5 Upsell Product
     * @paramOption 6 Fiance Charge
     * @paramOption 7 Special
     * @paramOption 8 Program
     * @paramOption 9 Subscription Plan
     * @paramOption 10 Special:Free Trial Days
     * @paramOption 12 Special: Order Total
     * @paramOption 13 Special: Category
     * @paramOption 14 Special: Shipping
     * @param double $price
     * @param itn $qty
     * @param string $desc
     * @param string $notes
     * @return bool
     */
    public function addOrderItem($ordId, $prodId, $type, $price, $qty, $desc, $notes)
    {
        return $this->makeApiCall("InvoiceService.addOrderItem", [
            (int)$ordId,
            (int)$prodId,
            (int)$type,
            $price,
            $qty,
            $desc,
            $notes
        ]);
    }

    /**
     * @method addPaymentPlan
     * @description add a payment plan to an order
     * @param int $ordId
     * @param bool $aCharge
     * @param int $ccId
     * @param int $merchId
     * @param int $retry
     * @param int $retryAmt
     * @param double $initialPmt
     * @param datetime $initialPmtDate
     * @param datetime $planStartDate
     * @param int $numPmts
     * @param int $pmtDays
     * @return bool
     */
    public function payPlan($ordId, $aCharge, $ccId, $merchId, $retry, $retryAmt, $initialPmt, $initialPmtDate, $planStartDate, $numPmts, $pmtDays)
    {
        return $this->makeApiCall("InvoiceService.addPaymentPlan", [
            (int)$ordId,
            $aCharge,
            (int)$ccId,
            (int)$merchId,
            (int)$retry,
            (int)$retryAmt,
            $initialPmt,
            $initialPmtDate,
            $planStartDate,
            (int)$numPmts,
            (int)$pmtDays
        ]);
    }

    /**
     * @method addRecurringOrder
     * @description creates a subscription for a contact
     */
    public function addRecurringOrder($contactId, $allowDuplicate, $programId, $merchantAccountId, $creditCardId, $affiliateId, $daysToCharge)
    {
        return $this->makeApiCall("RecurringOrderService.addRecurringOrder", [
            (int)$contactId,
            (boolean)$allowDuplicate,
            (int)$programId,
            (int)$merchantAccountId,
            (int)$creditCardId,
            (int)$affiliateId,
            (int)$daysToCharge
        ]);
    }

    /**
     * @method getRecurringOrder
     * @description gets details on a subscription
     */
    public function getRecurringOrder($recurringOrderId)
    {
        return $this->makeApiCall("RecurringOrderService.getRecurringOrder", [
            (int)$recurringOrderId
        ]);
    }

    /**
     * @method getAllRecurringOrders
     * @description gets all subscriptions for a contact
     */
    public function getAllRecurringOrders($contactId)
    {
        return $this->makeApiCall("RecurringOrderService.getAllRecurringOrders", [
            (int)$contactId
        ]);
    }

    /**
     * @method getPayments
     * @description gets payments for an invoice
     */
    public function getPayments($invoiceId)
    {
        return $this->makeApiCall("InvoiceService.getPayments", [
            (int)$invoiceId
        ]);
    }

    /**
     * @method getAllPaymentOptions
     * @description gets all payment options
     */
    public function getAllPaymentOptions()
    {
        return $this->makeApiCall("InvoiceService.getAllPaymentOptions", []);
    }

    /**
     * @method validateCreditCard
     * @description validates a credit card
     */
    public function validateCreditCard($creditCard)
    {
        return $this->makeApiCall("InvoiceService.validateCreditCard", [
            is_array($creditCard) ? $creditCard : (int)$creditCard
        ]);
    }

    /**
     * @method getFile
     * @description gets a file from Infusionsoft
     */
    public function getFile($fileId)
    {
        return $this->makeApiCall("FileService.getFile", [
            (int)$fileId
        ]);
    }

    /**
     * @method getOrderId
     * @description get the Order Id associated with an Invoice
     * @param int $invoiceId
     * @return int
     */
    public function getOrderId($invoiceId)
    {
        return $this->makeApiCall("InvoiceService.getOrderId", [
            (int)$invoiceId
        ]);
    }

    /**
     * @method chargeInvoice
     * @description charges an invoice using a credit card
     */
    public function chargeInvoice($invoiceId, $notes, $creditCardId, $merchantAccountId, $bypassCommissions)
    {
        return $this->makeApiCall("InvoiceService.chargeInvoice", [
            (int)$invoiceId,
            $notes,
            (int)$creditCardId,
            (int)$merchantAccountId,
            (boolean)$bypassCommissions
        ]);
    }

    /**
     * @method createBlankOrder
     * @description creates a blank order for a contact
     */
    public function createBlankOrder($contactId, $description, $orderDate, $leadAffiliateId = 0, $salesAffiliateId = 0)
    {
        return $this->makeApiCall("OrderService.createBlankOrder", [
            (int)$contactId,
            $description,
            $orderDate,
            (int)$leadAffiliateId,
            (int)$salesAffiliateId
        ]);
    }

    /**
     * @method addOrderCommissionOverride
     * @description adds a commission override to an order
     */
    public function addOrderCommissionOverride($orderId, $affiliateId, $productId, $percentage, $amount, $payoutType, $description, $date)
    {
        return $this->makeApiCall("OrderService.addOrderCommissionOverride", [
            (int)$orderId,
            (int)$affiliateId,
            (int)$productId,
            $percentage,
            $amount,
            (int)$payoutType,
            $description,
            $date
        ]);
    }

    /**
     * @method createInvoiceForRecurring
     * @description creates an invoice for a recurring order
     */
    public function createInvoiceForRecurring($recurringOrderId)
    {
        return $this->makeApiCall("OrderService.createInvoiceForRecurring", [
            (int)$recurringOrderId
        ]);
    }

    /**
     * @method getOrderByOrderId
     * @description retrieves an order by ID
     */
    public function getOrderByOrderId($orderId)
    {
        return $this->makeApiCall("OrderService.getOrderById", [
            (int)$orderId
        ]);
    }

    /**
     * @method getPayPlanStatus
     * @description gets the status of a payment plan
     */
    public function getPayPlanStatus($payPlanId)
    {
        return $this->makeApiCall("OrderService.getPayPlanStatus", [
            (int)$payPlanId
        ]);
    }

    /**
     * @method calculateAmountOwed
     * @description calculates amount owed on a payment plan
     */
    public function calculateAmountOwed($payPlanId)
    {
        return $this->makeApiCall("OrderService.calculateAmountOwed", [
            (int)$payPlanId
        ]);
    }

    /**
     * @method locateExistingCard
     * @description locates a creditcard Id from based on the last 4 digits
     * @param int $cid
     * @param string $last4
     * @return int
     */
    public function locateCard($cid, $last4)
    {
        return $this->makeApiCall("InvoiceService.locateExistingCard", [
            (int)$cid,
            $last4
        ]);
    }

    /**
     * @method updateSubscriptionNextBillDate
     * @description Updates the Next Bill Date on a Subscription
     * @param int $subscriptionId
     * @param date $nextBillDate
     * @return bool
     */
    public function updateSubscriptionNextBillDate($subscriptionId, $nextBillDate)
    {
        return $this->makeApiCall("InvoiceService.updateJobRecurringNextBillDate", [
            (int)$subscriptionId,
            $nextBillDate
        ]);
    }

    /**
     * @method recalculateTax
     * @description recalculates tax for a given invoice Id
     * @param $invoiceId
     * @return bool
     */
    public function recalculateTax($invoiceId)
    {
        return $this->makeApiCall("InvoiceService.recalculateTax", [
            (int)$invoiceId
        ]);
    }

    /**
     * @service Misc ifiSDK Functions
     */

    /**
     * @method infuDate
     * @description returns properly formatted dates.
     * @param $dateStr
     * @param $dateFrmt - Optional date format for UK formatted Applications
     * @return bool|string
     */
    public function infuDate($dateStr, $dateFrmt = 'US')
    {
        $dArray = date_parse($dateStr);
        if ($dArray['error_count'] < 1) {
            $tStamp =
                mktime($dArray['hour'], $dArray['minute'], $dArray['second'], $dArray['month'],
                    $dArray['day'], $dArray['year']);
            if ($dateFrmt == 'UK') {
                setlocale(LC_ALL, 'en_GB');
                return date('Y-d-m\TH:i:s', $tStamp);
            } else {
                return date('Ymd\TH:i:s', $tStamp);
            }
        } else {
            foreach ($dArray['errors'] as $err) {
                echo "ERROR: " . $err . "<br />";
            }
            die("The above errors prevented the application from executing properly.");
        }
    }

    /**
     * @method enableLogging
     * @description Function to Enable/Disable Logging
     * @param int $log
     */
    public function enableLogging($log)
    {
        $this->loggingEnabled = $log;
    }

    /**
     * @method getHandle
     * @description Creates CSV Resource
     * @param string $logname
     * @return resource
     */
    static protected function getHandle($logname)
    {
        if (!is_resource(self::$handle)) {
            self::$handle = fopen($logname, 'a+');
        }
        return self::$handle;
    }

    /**
     * @method log
     * @description Function for Logging Calls
     * @param array $data
     * @return mixed
     */
    private function log($data)
    {
        $logdata = $data;

        if ($this->logname == '') {
            $logname = dirname(__FILE__) . '/apilog.csv';
        } else {
            $logname = $this->logname;
        }

        if (!file_exists($logname)) {
            $this->getHandle($logname);
            fputcsv(self::$handle, array('Date', 'Method', 'Call', 'Start Time', 'Stop Time', 'Execution Time', 'Result', 'Error', 'Error Code'));
        } else {
            $this->getHandle($logname);
        }

        if (isset($logdata['Call'][0]->me['string'])) {
            if ($logdata['Call'][0]->me['string'] == 'CreditCard') {
                unset($logdata['Call'][1]->me['struct']);
                $logdata['Call'][1]->me['struct'] = 'Data Removed For Security';
            }
        }

        $logdata['Call'][0]->me['string'] = 'APIKEY';

        fputcsv(self::$handle, array(
            date('Y-m-d H:i:s', $logdata['Now']),
            $logdata['Method'],
            print_r(serialize($logdata['Call']), true),
            $logdata['Start'],
            $logdata['Stop'],
            ($logdata['Stop'] - $logdata['Start']),
            print_r(serialize($logdata['Result']), true),
            $logdata['Error'],
            $logdata['ErrorCode']
        ));
        fclose(self::$handle);

    }

    public function setLog($logPath)
    {
        $this->logname = $logPath;
    }

    /**
     * @service Order Service
     */

    /**
     * @method placeOrder
     * @description Builds, creates and charges an order.
     * @param int $contactId
     * @param int $creditCardId
     * @param int $payPlanId
     * @param array $productIds
     * @param array $subscriptionIds
     * @param bool $processSpecials
     * @param array $promoCodes
     * @param int $leadAff
     * @param int $saleAff
     * @return array
     */
    public function placeOrder($contactId, $creditCardId, $payPlanId, $productIds, $subscriptionIds, $processSpecials, $promoCodes, $leadAff = 0, $saleAff = 0)
    {
        return $this->makeApiCall("OrderService.placeOrder", [
            (int)$contactId,
            (int)$creditCardId,
            (int)$payPlanId,
            $productIds,
            $subscriptionIds,
            (boolean)$processSpecials,
            $promoCodes,
            (int)$leadAff,
            (int)$saleAff
        ]);
    }

    /**
     * @service Product Service
     */

    /**
     * @method getInventory
     * @description retrieves the current inventory level for a specific product
     * @param int $productId
     * @return int
     */
    public function getInventory($productId)
    {
        return $this->makeApiCall("ProductService.getInventory", [
            (int)$productId
        ]);
    }

    /**
     * @method incrementInventory
     * @description increments current inventory level by 1
     * @param int $productId
     * @return bool
     */
    public function incrementInventory($productId)
    {
        return $this->makeApiCall("ProductService.incrementInventory", [
            (int)$productId
        ]);
    }

    /**
     * @method decrementInventory
     * @description decrements current inventory level by 1
     * @param int $productId
     * @return bool
     */
    public function decrementInventory($productId)
    {
        return $this->makeApiCall("ProductService.decrementInventory", [
            (int)$productId
        ]);
    }

    /**
     * @method increaseInventory
     * @description increases inventory levels
     * @param int $productId
     * @param int $quantity
     * @return bool
     */
    public function increaseInventory($productId, $quantity)
    {
        return $this->makeApiCall("ProductService.increaseInventory", [
            (int)$productId,
            (int)$quantity
        ]);
    }

    /**
     * @method decreaseInventory
     * @description decreases inventory levels
     * @param int $productId
     * @param int $quantity
     * @return bool
     */
    public function decreaseInventory($productId, $quantity)
    {
        return $this->makeApiCall("ProductService.decreaseInventory", [
            (int)$productId,
            (int)$quantity
        ]);
    }

    /**
     * @method deactivateCreditCard
     * @description deactivate a credit card
     * @param int $creditCardId
     * @return bool
     */
    public function deactivateCreditCard($creditCardId)
    {
        return $this->makeApiCall("ProductService.deactivateCreditCard", [
            (int)$creditCardId
        ]);
    }

    /**
     * @service Search Service
     */

    /**
     * @method getSavedSearchResultsAllFields
     * @description returns a saved search with all fields
     * @param int $savedSearchId
     * @param int $userId
     * @param int $page
     * @return array
     */
    public function savedSearchAllFields($savedSearchId, $userId, $page)
    {
        return $this->makeApiCall("SearchService.getSavedSearchResultsAllFields", [
            (int)$savedSearchId,
            (int)$userId,
            (int)$page
        ]);
    }

    /**
     * @method getSavedSearchResults
     * @description returns a saved search with selected fields
     * @param int $savedSearchId
     * @param int $userId
     * @param int $page
     * @param array $fields
     * @return array
     */
    public function savedSearch($savedSearchId, $userId, $page, $fields)
    {
        return $this->makeApiCall("SearchService.getSavedSearchResults", [
            (int)$savedSearchId,
            (int)$userId,
            (int)$page,
            $fields
        ]);
    }

    /**
     * @method getAllReportColumns
     * @description returns the fields available in a saved report
     * @param int $savedSearchId
     * @param int $userId
     * @return array
     */
    public function getAvailableFields($savedSearchId, $userId)
    {
        return $this->makeApiCall("SearchService.getAllReportColumns", [
            (int)$savedSearchId,
            (int)$userId
        ]);
    }

    /**
     * @method getDefaultQuickSearch
     * @description returns the default quick search type for a user
     * @param int $userId
     * @return array
     */
    public function getDefaultQuickSearch($userId)
    {
        return $this->makeApiCall("SearchService.getDefaultQuickSearch", [
            (int)$userId
        ]);
    }

    /**
     * @method getAvailableQuickSearches
     * @description returns the available quick search types
     * @param int $userId
     * @return array
     */
    public function getQuickSearches($userId)
    {
        return $this->makeApiCall("SearchService.getAvailableQuickSearches", [
            (int)$userId
        ]);
    }

    /**
     * @method quickSearch
     * @description returns the results of a quick search
     * @param int $quickSearchType
     * @param int $userId
     * @param string $filterData
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function quickSearch($quickSearchType, $userId, $filterData, $page, $limit)
    {
        return $this->makeApiCall("SearchService.quickSearch", [
            $quickSearchType,
            (int)$userId,
            $filterData,
            (int)$page,
            (int)$limit
        ]);
    }

    /**
     * @service Service Call Service
     * @note also known as Ticket System. This service is deprecated
     */

    /**
     * @method addMoveNotes
     * @description Adds move notes to existing tickets
     * @param array $ticketList
     * @param string $moveNotes
     * @param int $moveToStageId
     * @param int $notifyIds
     * @return bool
     */
    public function addMoveNotes($ticketList, $moveNotes, $moveToStageId, $notifyIds)
    {
        return $this->makeApiCall("ServiceCallService.addMoveNotes", [
            $ticketList,
            $moveNotes,
            (int)$moveToStageId,
            $notifyIds
        ]);
    }

    /**
     * @method moveTicketStage
     * @description Moves a Ticket Stage
     * @param int $ticketID
     * @param string $ticketStage
     * @param string $moveNotes
     * @param string $notifyIds
     * @return bool
     */
    public function moveTicketStage($ticketID, $ticketStage, $moveNotes, $notifyIds)
    {
        return $this->makeApiCall("ServiceCallService.moveTicketStage", [
            (int)$ticketID,
            $ticketStage,
            $moveNotes,
            $notifyIds
        ]);
    }

    /**
     * @service Shipping Service
     */

    /**
     * @method getAllShippingOptions
     * @description get a list of shipping methods
     * @return array
     */
    public function getAllShippingOptions()
    {
        return $this->makeApiCall("ShippingService.getAllShippingOptions", []);
    }

    /**
     * @method getAllConfiguredShippingOptions
     * @description get a list of shipping methods
     * @return array
     */
    public function getAllConfiguredShippingOptions()
    {
        return $this->makeApiCall("ShippingService.getAllShippingOptions", []);
    }

    /**
     * @method getFlatRateShippingOption
     * @description retrieves details on a flat rate type shipping option
     * @param int $optionId
     * @return array
     */
    public function getFlatRateShippingOption($optionId)
    {
        return $this->makeApiCall("ShippingService.getFlatRateShippingOption", [
            (int)$optionId
        ]);
    }

    /**
     * @method getOrderTotalShippingOption
     * @description retrieves details on a order total type shipping option
     * @param int $optionId
     * @return array
     */
    public function getOrderTotalShippingOption($optionId)
    {
        return $this->makeApiCall("ShippingService.getOrderTotalShippingOption", [
            (int)$optionId
        ]);
    }

    /**
     * @method getOrderTotalShippingRanges
     * @description retrieves the pricing range details for the given Order Total shipping option
     * @param int $optionId
     * @return array
     */
    public function getOrderTotalShippingRanges($optionId)
    {
        return $this->makeApiCall("ShippingService.getOrderTotalShippingRanges", [
            (int)$optionId
        ]);
    }

    /**
     * @method getProductBasedShippingOption
     * @description retrieves details on a product based type shipping option
     * @param int $optionId
     * @return array
     */
    public function getProductBasedShippingOption($optionId)
    {
        return $this->makeApiCall("ShippingService.getProductBasedShippingOption", [
            (int)$optionId
        ]);
    }

    /**
     * @method getProductShippingPricesForProductShippingOption
     * @description retrieves the pricing for your per product shipping options
     * @param int $optionId
     * @return array
     */
    public function getProductShippingPricesForProductShippingOption($optionId)
    {
        return $this->makeApiCall("ShippingService.getProductShippingPricesForProductShippingOption", [
            (int)$optionId
        ]);
    }

    /**
     * @method getOrderQuantityShippingOption
     * @description retrieves details on a order quantity type shipping option
     * @param int $optionId
     * @return array
     */
    public function getOrderQuantityShippingOption($optionId)
    {
        return $this->makeApiCall("ShippingService.getOrderQuantityShippingOption", [
            (int)$optionId
        ]);
    }

    /**
     * @method getWeightBasedShippingOption
     * @description retrieves details on a weight based type shipping option
     * @param int $optionId
     * @return array
     */
    public function getWeightBasedShippingOption($optionId)
    {
        return $this->makeApiCall("ShippingService.getWeightBasedShippingOption", [
            (int)$optionId
        ]);
    }

    /**
     * @method getWeightBasedShippingRanges
     * @description retrieves the weight ranges for a weight based type shipping option
     * @param int $optionId
     * @return array
     */
    public function getWeightBasedShippingRanges($optionId)
    {
        return $this->makeApiCall("ShippingService.getWeightBasedShippingRanges", [
            (int)$optionId
        ]);
    }

    /**
     * @method getUpsShippingOption
     * @description retrieves the details around a UPS type shipping option
     * @param int $optionId
     * @return array
     */
    public function getUpsShippingOption($optionId)
    {
        return $this->makeApiCall("ShippingService.getUpsShippingOption", [
            (int)$optionId
        ]);
    }

    /**
     * @service Web Form Service
     */

    /**
     * @method getMap
     * @description returns web form titles and Id numbers from the application
     * @return array
     */
    public function getWebFormMap()
    {
        return $this->makeApiCall("WebFormService.getMap", []);
    }

    /**
     * @method getHTML
     * @description returns the HTML for the given web form
     * @param int $webFormId
     * @return string
     */
    public function getWebFormHtml($webFormId = 0)
    {
        return $this->makeApiCall("WebFormService.getHTML", [
            (int)$webFormId
        ]);
    }

    /**
     * @service Web Tracking Service
     */

    /**
     * @method getWebTrackingScriptTag
     * @description returns the web tracking javascript code
     * @return string
     */
    public function getWebTrackingServiceTag()
    {
        return $this->makeApiCall("WebTrackingService.getWebTrackingScriptTag", []);
    }

    /**
     * @method getWebTrackingScriptUrl
     * @description returns the url for the web tracking code
     * @return string
     */
    public function getWebTrackingScriptUrl()
    {
        return $this->makeApiCall("WebTrackingService.getWebTrackingScriptUrl", []);
    }

    /**
     * @method getProduct
     * @description retrieves a product from Infusionsoft
     */
    public function getProduct($productId, $includeInventory = false)
    {
        return $this->makeApiCall("ProductService.getProduct", [
            (int)$productId,
            (boolean)$includeInventory
        ]);
    }

    /**
     * @method getAllProducts
     * @description retrieves all products
     */
    public function getAllProducts($includeInventory = false)
    {
        return $this->makeApiCall("ProductService.getAllProducts", [
            (boolean)$includeInventory
        ]);
    }

    /**
     * @method getSubscriptionPlans
     * @description gets all subscription plans
     */
    public function getSubscriptionPlans($productId)
    {
        return $this->makeApiCall("ProductService.getSubscriptionPlans", [
            (int)$productId
        ]);
    }

    /**
     * @method getOptStatus
     * @description gets opt status for an email address
     */
    public function getOptStatus($email)
    {
        return $this->makeApiCall("APIEmailService.getOptStatus", [
            $email
        ]);
    }

}

?>