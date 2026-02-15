<?php
/**
 * Razorpay API Wrapper
 * Lightweight implementation for payment processing
 */

class RazorpayAPI {
    private $keyId;
    private $keySecret;
    private $baseUrl = 'https://api.razorpay.com/v1/';
    
    public function __construct($keyId, $keySecret) {
        $this->keyId = $keyId;
        $this->keySecret = $keySecret;
    }
    
    /**
     * Create Razorpay Order
     * @param float $amount Amount in rupees
     * @param string $currency Currency code (default: INR)
     * @param array $notes Additional order notes
     * @return array Order details
     */
    public function createOrder($amount, $currency = 'INR', $notes = []) {
        $data = [
            'amount' => $amount * 100, // Convert to paise
            'currency' => $currency,
            'notes' => $notes
        ];
        
        return $this->makeRequest('orders', 'POST', $data);
    }
    
    /**
     * Fetch Order Details
     * @param string $orderId Razorpay order ID
     * @return array Order details
     */
    public function fetchOrder($orderId) {
        return $this->makeRequest("orders/$orderId", 'GET');
    }
    
    /**
     * Verify Payment Signature
     * @param string $razorpayOrderId
     * @param string $razorpayPaymentId
     * @param string $razorpaySignature
     * @return bool True if signature is valid
     */
    public function verifyPaymentSignature($razorpayOrderId, $razorpayPaymentId, $razorpaySignature) {
        $expectedSignature = hash_hmac('sha256', $razorpayOrderId . '|' . $razorpayPaymentId, $this->keySecret);
        return hash_equals($expectedSignature, $razorpaySignature);
    }
    
    /**
     * Fetch Payment Details
     * @param string $paymentId Razorpay payment ID
     * @return array Payment details
     */
    public function fetchPayment($paymentId) {
        return $this->makeRequest("payments/$paymentId", 'GET');
    }
    
    /**
     * Create Refund
     * @param string $paymentId Razorpay payment ID
     * @param float $amount Amount to refund (optional, full refund if not specified)
     * @return array Refund details
     */
    public function createRefund($paymentId, $amount = null) {
        $data = [];
        if ($amount !== null) {
            $data['amount'] = $amount * 100; // Convert to paise
        }
        
        return $this->makeRequest("payments/$paymentId/refund", 'POST', $data);
    }
    
    /**
     * Make API Request
     * @param string $endpoint API endpoint
     * @param string $method HTTP method (GET/POST/PUT/DELETE)
     * @param array $data Request data
     * @return array Response data
     */
    private function makeRequest($endpoint, $method = 'GET', $data = []) {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->keyId . ':' . $this->keySecret);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new Exception('cURL Error: ' . $error);
        }
        
        $result = json_decode($response, true);
        
        if ($httpCode >= 400) {
            $errorMessage = $result['error']['description'] ?? 'Unknown error occurred';
            throw new Exception('Razorpay API Error: ' . $errorMessage);
        }
        
        return $result;
    }
}

?>
